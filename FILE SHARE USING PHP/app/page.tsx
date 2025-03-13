"use client"

import { useState, useRef, useCallback } from "react"
import { useDropzone } from "react-dropzone"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import { Slider } from "@/components/ui/slider"
import { Loader2, Upload, Shield, Clock, LinkIcon, Copy, Check } from "lucide-react"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { toast } from "@/components/ui/use-toast"
import { uploadFile } from "@/lib/actions"

export default function Home() {
  const [file, setFile] = useState<File | null>(null)
  const [isUploading, setIsUploading] = useState(false)
  const [uploadComplete, setUploadComplete] = useState(false)
  const [fileLink, setFileLink] = useState("")
  const [password, setPassword] = useState("")
  const [isPasswordProtected, setIsPasswordProtected] = useState(false)
  const [downloadLimit, setDownloadLimit] = useState(0)
  const [expirationDays, setExpirationDays] = useState(7)
  const linkRef = useRef<HTMLInputElement>(null)

  const onDrop = useCallback((acceptedFiles: File[]) => {
    if (acceptedFiles.length > 0) {
      setFile(acceptedFiles[0])
    }
  }, [])

  const { getRootProps, getInputProps, isDragActive } = useDropzone({
    onDrop,
    maxFiles: 1,
    maxSize: 100 * 1024 * 1024, // 100MB limit
  })

  const handleUpload = async () => {
    if (!file) return

    setIsUploading(true)

    try {
      const formData = new FormData()
      formData.append("file", file)
      formData.append("passwordProtected", isPasswordProtected ? "1" : "0")
      formData.append("password", password)
      formData.append("downloadLimit", downloadLimit.toString())
      formData.append("expirationDays", expirationDays.toString())

      const response = await uploadFile(formData)

      if (response.success) {
        setFileLink(response.fileUrl)
        setUploadComplete(true)
        toast({
          title: "Upload successful!",
          description: "Your file has been uploaded successfully.",
        })
      } else {
        toast({
          title: "Upload failed",
          description: response.message || "Something went wrong. Please try again.",
          variant: "destructive",
        })
      }
    } catch (error) {
      toast({
        title: "Upload failed",
        description: "Something went wrong. Please try again.",
        variant: "destructive",
      })
    } finally {
      setIsUploading(false)
    }
  }

  const copyToClipboard = () => {
    if (linkRef.current) {
      linkRef.current.select()
      document.execCommand("copy")
      toast({
        title: "Link copied!",
        description: "File link has been copied to clipboard.",
      })
    }
  }

  const resetForm = () => {
    setFile(null)
    setUploadComplete(false)
    setFileLink("")
    setPassword("")
    setIsPasswordProtected(false)
    setDownloadLimit(0)
    setExpirationDays(7)
  }

  return (
    <main className="flex min-h-screen flex-col items-center justify-center p-4 bg-background">
      <div className="w-full max-w-3xl mx-auto">
        <div className="text-center mb-8">
          <h1 className="text-4xl font-bold tracking-tight text-primary">FileShare</h1>
          <p className="text-muted-foreground mt-2">Upload, share, and manage your files securely</p>
        </div>

        <Tabs defaultValue="upload" className="w-full">
          <TabsList className="grid w-full grid-cols-2">
            <TabsTrigger value="upload">Upload</TabsTrigger>
            <TabsTrigger value="options">Options</TabsTrigger>
          </TabsList>

          <TabsContent value="upload">
            <Card>
              <CardHeader>
                <CardTitle>Upload File</CardTitle>
                <CardDescription>Drag and drop your file or click to browse</CardDescription>
              </CardHeader>
              <CardContent>
                {!uploadComplete ? (
                  <div
                    {...getRootProps()}
                    className={`border-2 border-dashed rounded-lg p-10 text-center cursor-pointer transition-colors ${
                      isDragActive ? "border-primary bg-primary/10" : "border-border"
                    }`}
                  >
                    <input {...getInputProps()} />
                    <Upload className="h-10 w-10 mx-auto mb-4 text-muted-foreground" />
                    {isDragActive ? (
                      <p>Drop the file here...</p>
                    ) : (
                      <div>
                        <p className="mb-2">Drag & drop a file here, or click to select</p>
                        <p className="text-sm text-muted-foreground">Maximum file size: 100MB</p>
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="space-y-4">
                    <div className="flex items-center p-4 bg-muted rounded-lg">
                      <Check className="h-5 w-5 text-green-500 mr-2" />
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium truncate">{file?.name}</p>
                        <p className="text-xs text-muted-foreground">
                          {(file?.size && (file.size / 1024 / 1024).toFixed(2)) || 0} MB
                        </p>
                      </div>
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="file-link">Share Link</Label>
                      <div className="flex space-x-2">
                        <Input id="file-link" ref={linkRef} value={fileLink} readOnly className="flex-1" />
                        <Button variant="outline" size="icon" onClick={copyToClipboard} title="Copy to clipboard">
                          <Copy className="h-4 w-4" />
                        </Button>
                      </div>
                    </div>
                  </div>
                )}
              </CardContent>
              <CardFooter className="flex justify-between">
                {!uploadComplete ? (
                  <>
                    <Button variant="outline" onClick={resetForm} disabled={!file || isUploading}>
                      Clear
                    </Button>
                    <Button onClick={handleUpload} disabled={!file || isUploading}>
                      {isUploading ? (
                        <>
                          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                          Uploading...
                        </>
                      ) : (
                        <>Upload</>
                      )}
                    </Button>
                  </>
                ) : (
                  <Button onClick={resetForm} className="w-full">
                    Upload Another File
                  </Button>
                )}
              </CardFooter>
            </Card>
          </TabsContent>

          <TabsContent value="options">
            <Card>
              <CardHeader>
                <CardTitle>File Options</CardTitle>
                <CardDescription>Configure security and sharing options</CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                      <Label htmlFor="password-protection">Password Protection</Label>
                      <p className="text-sm text-muted-foreground">Protect your file with a password</p>
                    </div>
                    <Switch
                      id="password-protection"
                      checked={isPasswordProtected}
                      onCheckedChange={setIsPasswordProtected}
                    />
                  </div>

                  {isPasswordProtected && (
                    <div className="space-y-2">
                      <Label htmlFor="password">Password</Label>
                      <Input
                        id="password"
                        type="password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        placeholder="Enter a secure password"
                      />
                    </div>
                  )}
                </div>

                <div className="space-y-4">
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <Label htmlFor="download-limit">Download Limit</Label>
                      <span className="text-sm text-muted-foreground">
                        {downloadLimit === 0 ? "Unlimited" : `${downloadLimit} downloads`}
                      </span>
                    </div>
                    <Slider
                      id="download-limit"
                      value={[downloadLimit]}
                      onValueChange={(value) => setDownloadLimit(value[0])}
                      max={20}
                      step={1}
                    />
                  </div>
                </div>

                <div className="space-y-4">
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <Label htmlFor="expiration">Expiration Time</Label>
                      <span className="text-sm text-muted-foreground">
                        {expirationDays === 0 ? "Never expires" : `${expirationDays} days`}
                      </span>
                    </div>
                    <Slider
                      id="expiration"
                      value={[expirationDays]}
                      onValueChange={(value) => setExpirationDays(value[0])}
                      max={30}
                      step={1}
                    />
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>

        <div className="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card className="bg-primary/5">
            <CardContent className="pt-6">
              <Shield className="h-8 w-8 mb-2 text-primary" />
              <h3 className="text-lg font-medium">Secure Storage</h3>
              <p className="text-sm text-muted-foreground mt-2">Your files are encrypted and stored securely</p>
            </CardContent>
          </Card>

          <Card className="bg-primary/5">
            <CardContent className="pt-6">
              <LinkIcon className="h-8 w-8 mb-2 text-primary" />
              <h3 className="text-lg font-medium">Easy Sharing</h3>
              <p className="text-sm text-muted-foreground mt-2">Share files with a simple link</p>
            </CardContent>
          </Card>

          <Card className="bg-primary/5">
            <CardContent className="pt-6">
              <Clock className="h-8 w-8 mb-2 text-primary" />
              <h3 className="text-lg font-medium">Expiration Control</h3>
              <p className="text-sm text-muted-foreground mt-2">Set download limits and expiration dates</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </main>
  )
}


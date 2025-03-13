"use client"

import { useState, useEffect } from "react"
import { useParams } from "next/navigation"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Loader2, Download, FileIcon, Lock, AlertTriangle } from "lucide-react"
import { toast } from "@/components/ui/use-toast"
import { getFileInfo, downloadFile } from "@/lib/actions"

export default function DownloadPage() {
  const params = useParams()
  const fileId = params.id as string

  const [fileInfo, setFileInfo] = useState<any>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")
  const [password, setPassword] = useState("")
  const [isDownloading, setIsDownloading] = useState(false)

  useEffect(() => {
    const fetchFileInfo = async () => {
      try {
        const response = await getFileInfo(fileId)
        if (response.success) {
          setFileInfo(response.fileInfo)
        } else {
          setError(response.message || "File not found or has expired")
        }
      } catch (err) {
        setError("Failed to load file information")
      } finally {
        setLoading(false)
      }
    }

    fetchFileInfo()
  }, [fileId])

  const handleDownload = async () => {
    setIsDownloading(true)

    try {
      const response = await downloadFile(fileId, password)

      if (response.success) {
        // Create a temporary link to download the file
        const link = document.createElement("a")
        link.href = response.downloadUrl
        link.setAttribute("download", fileInfo.fileName)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)

        toast({
          title: "Download started",
          description: "Your file is being downloaded.",
        })
      } else {
        toast({
          title: "Download failed",
          description: response.message || "Failed to download file",
          variant: "destructive",
        })
      }
    } catch (error) {
      toast({
        title: "Download failed",
        description: "Something went wrong. Please try again.",
        variant: "destructive",
      })
    } finally {
      setIsDownloading(false)
    }
  }

  if (loading) {
    return (
      <div className="flex min-h-screen flex-col items-center justify-center p-4 bg-background">
        <Loader2 className="h-8 w-8 animate-spin text-primary mb-4" />
        <p>Loading file information...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex min-h-screen flex-col items-center justify-center p-4 bg-background">
        <Card className="w-full max-w-md">
          <CardHeader>
            <CardTitle className="text-center">File Not Available</CardTitle>
          </CardHeader>
          <CardContent className="text-center">
            <AlertTriangle className="h-12 w-12 mx-auto text-destructive mb-4" />
            <p>{error}</p>
          </CardContent>
        </Card>
      </div>
    )
  }

  return (
    <div className="flex min-h-screen flex-col items-center justify-center p-4 bg-background">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold tracking-tight text-primary">FileShare</h1>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Download File</CardTitle>
            <CardDescription>
              {fileInfo.passwordProtected ? "This file is password protected" : "Ready to download"}
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center p-4 bg-muted rounded-lg">
              <FileIcon className="h-10 w-10 text-primary mr-4" />
              <div>
                <p className="font-medium">{fileInfo.fileName}</p>
                <p className="text-sm text-muted-foreground">{(fileInfo.fileSize / 1024 / 1024).toFixed(2)} MB</p>
              </div>
            </div>

            {fileInfo.downloadLimit > 0 && (
              <div className="text-sm text-muted-foreground">
                Downloads remaining: {fileInfo.downloadsRemaining} of {fileInfo.downloadLimit}
              </div>
            )}

            {fileInfo.expirationDate && (
              <div className="text-sm text-muted-foreground">
                Expires on: {new Date(fileInfo.expirationDate).toLocaleDateString()}
              </div>
            )}

            {fileInfo.passwordProtected && (
              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <div className="flex items-center space-x-2">
                  <Lock className="h-4 w-4 text-muted-foreground" />
                  <Input
                    id="password"
                    type="password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="Enter file password"
                  />
                </div>
              </div>
            )}
          </CardContent>
          <CardFooter>
            <Button
              className="w-full"
              onClick={handleDownload}
              disabled={isDownloading || (fileInfo.passwordProtected && !password)}
            >
              {isDownloading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Downloading...
                </>
              ) : (
                <>
                  <Download className="mr-2 h-4 w-4" />
                  Download File
                </>
              )}
            </Button>
          </CardFooter>
        </Card>
      </div>
    </div>
  )
}


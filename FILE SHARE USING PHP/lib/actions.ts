"use server"
import { v4 as uuidv4 } from "uuid"
import fs from "fs"
import path from "path"
import { writeFile } from "fs/promises"
import { db } from "@/lib/db"

// Ensure uploads directory exists
const uploadsDir = path.join(process.cwd(), "uploads")
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true })
}

export async function uploadFile(formData: FormData) {
  try {
    const file = formData.get("file") as File
    const passwordProtected = formData.get("passwordProtected") === "1"
    const password = formData.get("password") as string
    const downloadLimit = Number.parseInt(formData.get("downloadLimit") as string) || 0
    const expirationDays = Number.parseInt(formData.get("expirationDays") as string) || 7

    if (!file) {
      return { success: false, message: "No file provided" }
    }

    // Generate a unique ID for the file
    const fileId = uuidv4()
    const fileName = file.name
    const fileSize = file.size
    const fileType = file.type

    // Create a unique filename to store on disk
    const storedFileName = `${fileId}-${fileName.replace(/[^a-zA-Z0-9.-]/g, "_")}`
    const filePath = path.join(uploadsDir, storedFileName)

    // Save the file to disk
    const bytes = await file.arrayBuffer()
    const buffer = Buffer.from(bytes)
    await writeFile(filePath, buffer)

    // Calculate expiration date if needed
    let expirationDate = null
    if (expirationDays > 0) {
      expirationDate = new Date()
      expirationDate.setDate(expirationDate.getDate() + expirationDays)
    }

    // Store file metadata in database
    await db.file.create({
      data: {
        id: fileId,
        fileName,
        storedFileName,
        fileSize,
        fileType,
        passwordProtected,
        password: passwordProtected ? password : null,
        downloadLimit: downloadLimit > 0 ? downloadLimit : null,
        downloadsCount: 0,
        expirationDate,
      },
    })

    // Generate the file URL
    const fileUrl = `${process.env.NEXT_PUBLIC_APP_URL}/download/${fileId}`

    return {
      success: true,
      fileUrl,
      fileId,
    }
  } catch (error) {
    console.error("Upload error:", error)
    return { success: false, message: "Failed to upload file" }
  }
}

export async function getFileInfo(fileId: string) {
  try {
    const file = await db.file.findUnique({
      where: { id: fileId },
    })

    if (!file) {
      return { success: false, message: "File not found" }
    }

    // Check if file has expired
    if (file.expirationDate && new Date() > file.expirationDate) {
      return { success: false, message: "This file has expired" }
    }

    // Check if download limit has been reached
    if (file.downloadLimit && file.downloadsCount >= file.downloadLimit) {
      return { success: false, message: "Download limit has been reached" }
    }

    return {
      success: true,
      fileInfo: {
        fileName: file.fileName,
        fileSize: file.fileSize,
        fileType: file.fileType,
        passwordProtected: file.passwordProtected,
        downloadLimit: file.downloadLimit || 0,
        downloadsRemaining: file.downloadLimit ? file.downloadLimit - file.downloadsCount : null,
        expirationDate: file.expirationDate,
      },
    }
  } catch (error) {
    console.error("Get file info error:", error)
    return { success: false, message: "Failed to get file information" }
  }
}

export async function downloadFile(fileId: string, password: string) {
  try {
    const file = await db.file.findUnique({
      where: { id: fileId },
    })

    if (!file) {
      return { success: false, message: "File not found" }
    }

    // Check if file has expired
    if (file.expirationDate && new Date() > file.expirationDate) {
      return { success: false, message: "This file has expired" }
    }

    // Check if download limit has been reached
    if (file.downloadLimit && file.downloadsCount >= file.downloadLimit) {
      return { success: false, message: "Download limit has been reached" }
    }

    // Check password if file is password protected
    if (file.passwordProtected && file.password !== password) {
      return { success: false, message: "Incorrect password" }
    }

    // Increment download count
    await db.file.update({
      where: { id: fileId },
      data: { downloadsCount: { increment: 1 } },
    })

    // Generate a temporary download URL
    const filePath = path.join(uploadsDir, file.storedFileName)

    // In a real application, you would generate a signed URL or use a more secure method
    // For this example, we'll just return a direct path to the file
    const downloadUrl = `/api/download/${fileId}`

    return {
      success: true,
      downloadUrl,
    }
  } catch (error) {
    console.error("Download error:", error)
    return { success: false, message: "Failed to download file" }
  }
}


import { type NextRequest, NextResponse } from "next/server"
import fs from "fs"
import path from "path"
import { db } from "@/lib/db"

export async function GET(request: NextRequest, { params }: { params: { id: string } }) {
  try {
    const fileId = params.id

    // Get file information from database
    const file = await db.file.findUnique({
      where: { id: fileId },
    })

    if (!file) {
      return new NextResponse("File not found", { status: 404 })
    }

    // Check if file has expired
    if (file.expirationDate && new Date() > file.expirationDate) {
      return new NextResponse("This file has expired", { status: 410 })
    }

    // Check if download limit has been reached
    if (file.downloadLimit && file.downloadsCount > file.downloadLimit) {
      return new NextResponse("Download limit has been reached", { status: 403 })
    }

    // Get the file path
    const filePath = path.join(process.cwd(), "uploads", file.storedFileName)

    // Check if file exists
    if (!fs.existsSync(filePath)) {
      return new NextResponse("File not found on server", { status: 404 })
    }

    // Read the file
    const fileBuffer = fs.readFileSync(filePath)

    // Set appropriate headers
    const headers = new Headers()
    headers.set("Content-Disposition", `attachment; filename="${file.fileName}"`)
    headers.set("Content-Type", file.fileType || "application/octet-stream")
    headers.set("Content-Length", file.fileSize.toString())

    return new NextResponse(fileBuffer, {
      headers,
    })
  } catch (error) {
    console.error("Download API error:", error)
    return new NextResponse("Internal Server Error", { status: 500 })
  }
}


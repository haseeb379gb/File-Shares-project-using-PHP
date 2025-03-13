document.addEventListener("DOMContentLoaded", () => {
  // Elements
  const dropArea = document.getElementById("dropArea")
  const fileInput = document.getElementById("fileInput")
  const fileInfo = document.getElementById("fileInfo")
  const fileName = document.getElementById("fileName")
  const fileSize = document.getElementById("fileSize")
  const uploadForm = document.getElementById("uploadForm")
  const uploadButton = document.getElementById("uploadButton")
  const clearButton = document.getElementById("clearButton")
  const uploadProgress = document.getElementById("uploadProgress")
  const progressBar = uploadProgress?.querySelector(".progress-bar")
  const uploadResult = document.getElementById("uploadResult")
  const shareLink = document.getElementById("shareLink")

  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))

  // Drag and drop functionality
  if (dropArea) {
    // Prevent default drag behaviors
    ;["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
      dropArea.addEventListener(eventName, preventDefaults, false)
      document.body.addEventListener(eventName, preventDefaults, false)
    })
    // Highlight drop zone when item is dragged over it
    ;["dragenter", "dragover"].forEach((eventName) => {
      dropArea.addEventListener(eventName, highlight, false)
    })
    ;["dragleave", "drop"].forEach((eventName) => {
      dropArea.addEventListener(eventName, unhighlight, false)
    })

    // Handle dropped files
    dropArea.addEventListener("drop", handleDrop, false)

    function preventDefaults(e) {
      e.preventDefault()
      e.stopPropagation()
    }

    function highlight(e) {
      dropArea.classList.add("dragover")
    }

    function unhighlight(e) {
      dropArea.classList.remove("dragover")
    }

    function handleDrop(e) {
      const dt = e.dataTransfer
      const files = dt.files

      if (files.length > 0) {
        handleFiles(files[0])
      }
    }

    // Handle file selection via input
    fileInput?.addEventListener("change", function () {
      if (this.files.length > 0) {
        handleFiles(this.files[0])
      }
    })
  }

  function handleFiles(file) {
    // Check file size (100MB limit)
    const maxSize = 100 * 1024 * 1024 // 100MB in bytes
    if (file.size > maxSize) {
      alert("File size exceeds the maximum limit of 100MB.")
      return
    }

    // Display file info
    if (fileName && fileSize) {
      fileName.textContent = file.name
      fileSize.textContent = formatFileSize(file.size)

      document.querySelector(".drop-message")?.classList.add("d-none")
      fileInfo?.classList.remove("d-none")
    }

    // Enable upload and clear buttons
    if (uploadButton) uploadButton.disabled = false
    if (clearButton) clearButton.disabled = false
  }

  // Format file size
  function formatFileSize(bytes) {
    const units = ["B", "KB", "MB", "GB", "TB"]
    let size = bytes
    let unitIndex = 0

    while (size >= 1024 && unitIndex < units.length - 1) {
      size /= 1024
      unitIndex++
    }

    return `${size.toFixed(2)} ${units[unitIndex]}`
  }

  // Clear button functionality
  if (clearButton) {
    clearButton.addEventListener("click", () => {
      resetForm()
    })
  }

  function resetForm() {
    if (fileInput) fileInput.value = ""
    if (document.querySelector(".drop-message")) {
      document.querySelector(".drop-message").classList.remove("d-none")
    }
    if (fileInfo) fileInfo.classList.add("d-none")
    if (uploadButton) uploadButton.disabled = true
    if (clearButton) clearButton.disabled = true
    if (uploadProgress) uploadProgress.classList.add("d-none")
    if (uploadResult) uploadResult.classList.add("d-none")
  }

  // Handle form submission
  if (uploadForm) {
    uploadForm.addEventListener("submit", function (e) {
      e.preventDefault()

      // Show progress bar
      if (uploadProgress) {
        uploadProgress.classList.remove("d-none")
        if (progressBar) progressBar.style.width = "0%"
      }

      // Disable buttons during upload
      if (uploadButton) uploadButton.disabled = true
      if (clearButton) clearButton.disabled = true

      // Create FormData object
      const formData = new FormData(this)

      // Create and configure XMLHttpRequest
      const xhr = new XMLHttpRequest()
      xhr.open("POST", "upload.php", true)

      // Track upload progress
      xhr.upload.addEventListener("progress", (e) => {
        if (e.lengthComputable && progressBar) {
          const percentComplete = (e.loaded / e.total) * 100
          progressBar.style.width = percentComplete + "%"
        }
      })

      // Handle response
      xhr.addEventListener("load", () => {
        if (xhr.status === 200) {
          try {
            const response = JSON.parse(xhr.responseText)

            if (response.success) {
              // Show success message and share link
              if (shareLink) shareLink.value = response.file_url
              if (uploadResult) uploadResult.classList.remove("d-none")

              // Hide progress bar
              if (uploadProgress) uploadProgress.classList.add("d-none")
            } else {
              // Show error message
              alert(response.message || "Upload failed. Please try again.")
              resetForm()
            }
          } catch (error) {
            alert("Upload failed. Please try again.")
            resetForm()
          }
        } else {
          alert("Upload failed. Please try again.")
          resetForm()
        }
      })

      // Handle network errors
      xhr.addEventListener("error", () => {
        alert("Network error. Please try again.")
        resetForm()
      })

      // Send the form data
      xhr.send(formData)
    })
  }

  // Copy link functionality
  const copyButtons = document.querySelectorAll(".copy-btn")
  copyButtons.forEach((button) => {
    button.addEventListener("click", function () {
      // Find the closest input field
      const linkInput = this.closest(".input-group").querySelector("input")

      // Select the text
      linkInput.select()
      linkInput.setSelectionRange(0, 99999) // For mobile devices

      // Copy to clipboard
      document.execCommand("copy")

      // Show feedback
      const feedbackElement = this.closest(".card-body").querySelector(".copy-feedback")
      if (feedbackElement) {
        feedbackElement.classList.remove("d-none")

        // Hide feedback after 3 seconds
        setTimeout(() => {
          feedbackElement.classList.add("d-none")
        }, 3000)
      }

      // Update tooltip
      const tooltip = bootstrap.Tooltip.getInstance(this)
      if (tooltip) {
        tooltip.hide()
        this.setAttribute("data-bs-original-title", "Copied!")
        tooltip.show()

        // Reset tooltip after 2 seconds
        setTimeout(() => {
          this.setAttribute("data-bs-original-title", "Copy to clipboard")
        }, 2000)
      }
    })
  })
})


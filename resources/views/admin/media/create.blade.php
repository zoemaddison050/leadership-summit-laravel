@extends('layouts.admin')

@section('title', 'Upload Files')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Upload Files</h1>
                <a href="{{ route('admin.media.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Media Library
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" id="uploadForm">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label">Select Files</label>
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-content">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h5>Drag and drop files here</h5>
                                    <p class="text-muted">or click to browse</p>
                                    <input type="file" name="files[]" id="fileInput" multiple
                                        accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv"
                                        class="d-none">
                                    <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                                        Choose Files
                                    </button>
                                </div>
                            </div>
                            @error('files.*')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="filePreview" class="mb-4" style="display: none;">
                            <h6>Selected Files:</h6>
                            <div id="fileList" class="row g-3"></div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.media.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>
                                <i class="fas fa-upload"></i> Upload Files
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        padding: 3rem 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .upload-area:hover,
    .upload-area.dragover {
        border-color: var(--primary-color);
        background-color: #f8fafc;
    }

    .upload-area.dragover {
        border-style: solid;
    }

    .file-preview-item {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        background: white;
    }

    .file-preview-image {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
    }

    .file-preview-icon {
        width: 100%;
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
        color: #6b7280;
    }

    .file-info {
        font-size: 0.9rem;
    }

    .file-name {
        font-weight: 600;
        margin-bottom: 0.25rem;
        word-break: break-word;
    }

    .file-size {
        color: #6b7280;
        font-size: 0.8rem;
    }

    .remove-file {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        cursor: pointer;
    }

    .remove-file:hover {
        background: #ef4444;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');
        const fileList = document.getElementById('fileList');
        const uploadBtn = document.getElementById('uploadBtn');
        let selectedFiles = [];

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });

        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            const files = Array.from(this.files);
            handleFiles(files);
        });

        function handleFiles(files) {
            selectedFiles = files;
            displayFilePreview();
            updateUploadButton();
        }

        function displayFilePreview() {
            fileList.innerHTML = '';

            if (selectedFiles.length === 0) {
                filePreview.style.display = 'none';
                return;
            }

            filePreview.style.display = 'block';

            selectedFiles.forEach((file, index) => {
                const col = document.createElement('div');
                col.className = 'col-md-3 col-sm-4 col-6';

                const item = document.createElement('div');
                item.className = 'file-preview-item position-relative';

                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-file';
                removeBtn.innerHTML = '×';
                removeBtn.onclick = () => removeFile(index);

                let preview = '';
                if (file.type.startsWith('image/')) {
                    const url = URL.createObjectURL(file);
                    preview = `<img src="${url}" class="file-preview-image" alt="${file.name}">`;
                } else {
                    const icon = getFileIcon(file.type);
                    preview = `<div class="file-preview-icon"><i class="fas fa-${icon} fa-3x"></i></div>`;
                }

                item.innerHTML = `
                ${preview}
                <div class="file-info">
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${formatFileSize(file.size)}</div>
                </div>
                <div class="mt-2">
                    <input type="text" class="form-control form-control-sm mb-1" 
                           placeholder="Alt text (for images)" name="alt_text[${index}]">
                    <textarea class="form-control form-control-sm" rows="2" 
                              placeholder="Description" name="description[${index}]"></textarea>
                </div>
            `;

                item.appendChild(removeBtn);
                col.appendChild(item);
                fileList.appendChild(col);
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateFileInput();
            displayFilePreview();
            updateUploadButton();
        }

        function updateFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        }

        function updateUploadButton() {
            uploadBtn.disabled = selectedFiles.length === 0;
        }

        function getFileIcon(mimeType) {
            if (mimeType.startsWith('video/')) return 'video';
            if (mimeType.startsWith('audio/')) return 'music';
            if (mimeType.includes('pdf')) return 'file-pdf';
            if (mimeType.includes('word')) return 'file-word';
            if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'file-excel';
            if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) return 'file-powerpoint';
            return 'file';
        }

        function formatFileSize(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = bytes;
            let unitIndex = 0;

            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex++;
            }

            return Math.round(size * 100) / 100 + ' ' + units[unitIndex];
        }
    });
</script>
@endsection
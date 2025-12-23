import React, { useState } from 'react';
import { useMealChoices } from '../hooks/useMealChoices';

const FileUpload = () => {
  const { uploadFile, isUploading, uploadError } = useMealChoices();
  const [selectedFile, setSelectedFile] = useState(null);
  const [uploadStatus, setUploadStatus] = useState(null);

  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  };

  const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
      setUploadStatus({ type: 'error', message: 'File size must be less than 5MB' });
      setSelectedFile(null);
      return;
    }

    // Validate file type
    const validTypes = [
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'application/vnd.ms-excel',
      'text/csv',
      'text/plain',
    ];
    const validExtensions = ['.xlsx', '.xls', '.csv'];
    const fileName = file.name.toLowerCase();
    const hasValidExtension = validExtensions.some(ext => fileName.endsWith(ext));
    
    if (!validTypes.includes(file.type) && !hasValidExtension) {
      setUploadStatus({ type: 'error', message: 'Please upload a valid Excel or CSV file (.xlsx, .xls, or .csv)' });
      setSelectedFile(null);
      return;
    }

    setSelectedFile(file);
    setUploadStatus(null);
  };

  const handleUpload = () => {
    if (!selectedFile) {
      setUploadStatus({ type: 'error', message: 'Please select a file first' });
      return;
    }

    setUploadStatus(null);
    uploadFile(selectedFile, {
      onSuccess: (data) => {
        const message = data?.message || 'File uploaded successfully!';
        const details = data?.created || data?.updated ? 
          ` (${data.created || 0} created, ${data.updated || 0} updated)` : '';
        setUploadStatus({ type: 'success', message: message + details });
        setSelectedFile(null);
        // Reset file input
        const fileInput = document.querySelector('input[type="file"]');
        if (fileInput) fileInput.value = '';
      },
      onError: (error) => {
        setUploadStatus({ type: 'error', message: error.message || 'Upload failed' });
      },
    });
  };

  return (
    <div className="upload-section">
      <h2>📤 Upload Excel File</h2>
      <div className="upload-controls">
        <div className="file-input-wrapper">
          <input
            type="file"
            accept=".xlsx,.xls,.csv"
            onChange={handleFileSelect}
            disabled={isUploading}
            id="file-input"
          />
        </div>
        <button
          className="upload-button"
          onClick={handleUpload}
          disabled={!selectedFile || isUploading}
        >
          {isUploading ? '⏳ Uploading...' : '🚀 Upload'}
        </button>
      </div>
      
      {selectedFile && (
        <div className="selected-file">
          <strong>Selected:</strong> {selectedFile.name} ({formatFileSize(selectedFile.size)})
        </div>
      )}

      {uploadStatus && (
        <div className={`status-message ${uploadStatus.type}`}>
          {uploadStatus.type === 'success' ? '✅' : '❌'} {uploadStatus.message}
        </div>
      )}
      
      {uploadError && !uploadStatus && (
        <div className="status-message error">
          ❌ {uploadError.message || 'Upload failed'}
        </div>
      )}
    </div>
  );
};

export default FileUpload;


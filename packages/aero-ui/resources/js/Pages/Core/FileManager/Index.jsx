import { useState, useRef } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import {
  IndexPageLayout,
  Button,
  HStack, VStack,
  Text,
  Mono,
  Field,
  Input,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function formatBytes(bytes) {
  if (!bytes) return '—';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function fileIcon(name) {
  const ext = (name || '').split('.').pop().toLowerCase();
  if (['jpg','jpeg','png','gif','webp','svg'].includes(ext)) return '🖼';
  if (['pdf'].includes(ext))                                   return '📕';
  if (['xlsx','xls','csv'].includes(ext))                      return '📊';
  if (['docx','doc'].includes(ext))                            return '📝';
  if (['zip','tar','gz'].includes(ext))                        return '🗜';
  return '📄';
}

export default function FileManagerIndex({ files = [], path = '', folders = [] }) {
  const toast      = useToast();
  const fileInputRef = useRef(null);

  const canUpload        = useHRMAC('core.file_manager.media_library.upload');
  const canDelete        = useHRMAC('core.file_manager.media_library.delete');
  const canCreateFolder  = useHRMAC('core.file_manager.media_library.create_folder');

  const [currentPath,   setCurrentPath]   = useState(path);
  const [currentFiles,  setCurrentFiles]  = useState(files);
  const [currentFolders,setCurrentFolders]= useState(folders);
  const [loading,       setLoading]       = useState(false);
  const [uploading,     setUploading]     = useState(false);
  const [showFolderModal, setShowFolderModal] = useState(false);
  const [folderName,    setFolderName]    = useState('');
  const [creatingFolder, setCreatingFolder] = useState(false);

  const navigate = async newPath => {
    setLoading(true);
    try {
      const { data } = await axios.get(route('core.file-manager.browse'), { params: { path: newPath } });
      setCurrentPath(newPath);
      setCurrentFiles(data.files   ?? []);
      setCurrentFolders(data.folders ?? []);
    } catch {
      toast.error('Failed to load directory.');
    } finally {
      setLoading(false);
    }
  };

  const breadcrumbParts = currentPath ? currentPath.split('/').filter(Boolean) : [];

  const handleUpload = async e => {
    const file = e.target.files?.[0];
    if (!file) return;
    setUploading(true);
    const fd = new FormData();
    fd.append('file', file);
    fd.append('path', currentPath);
    try {
      await axios.post(route('core.file-manager.upload'), fd);
      toast.success('File uploaded.');
      await navigate(currentPath);
    } catch {
      toast.error('Upload failed.');
    } finally {
      setUploading(false);
      e.target.value = '';
    }
  };

  const handleDeleteFile = async item => {
    if (!confirm(`Delete "${item.name}"?`)) return;
    try {
      await axios.delete(route('core.file-manager.destroy'), { data: { path: item.path } });
      toast.success('Deleted.');
      await navigate(currentPath);
    } catch {
      toast.error('Delete failed.');
    }
  };

  const handleDeleteFolder = async folder => {
    if (!confirm(`Delete folder "${folder.name}" and all its contents?`)) return;
    try {
      await axios.delete(route('core.file-manager.destroy'), { data: { path: folder.path, type: 'folder' } });
      toast.success('Folder deleted.');
      await navigate(currentPath);
    } catch {
      toast.error('Failed to delete folder.');
    }
  };

  const handleCreateFolder = async () => {
    if (!folderName.trim()) return;
    setCreatingFolder(true);
    try {
      await axios.post(route('core.file-manager.create-folder'), { path: currentPath, name: folderName.trim() });
      toast.success('Folder created.');
      setShowFolderModal(false);
      setFolderName('');
      await navigate(currentPath);
    } catch {
      toast.error('Failed to create folder.');
    } finally {
      setCreatingFolder(false);
    }
  };

  return (
    <IndexPageLayout
      title="File Manager"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'File Manager' },
      ]}
      description="Browse, upload, and manage files and folders."
      actions={
        <HStack gap={2}>
          {canCreateFolder && (
            <Button intent="ghost" leftIcon="folder" onClick={() => { setFolderName(''); setShowFolderModal(true); }}>
              New Folder
            </Button>
          )}
          {canUpload && (
            <>
              <input
                ref={fileInputRef}
                type="file"
                className="aeos-visually-hidden"
                onChange={handleUpload}
              />
              <Button intent="primary" leftIcon="upload" loading={uploading} onClick={() => fileInputRef.current?.click()}>
                Upload File
              </Button>
            </>
          )}
        </HStack>
      }
    >
      <style>{`.aeos-visually-hidden{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}`}</style>

      {/* Breadcrumb */}
      <HStack gap={1} align="center" wrap>
        <Button intent="ghost" size="sm" onClick={() => navigate('')}>Home</Button>
        {breadcrumbParts.map((part, idx) => {
          const partPath = breadcrumbParts.slice(0, idx + 1).join('/');
          return (
            <HStack key={idx} gap={1} align="center">
              <Text tone="secondary">/</Text>
              <Button intent="ghost" size="sm" onClick={() => navigate(partPath)}>{part}</Button>
            </HStack>
          );
        })}
      </HStack>

      {loading ? (
        <Text tone="secondary">Loading…</Text>
      ) : (
        <VStack gap={4}>
          {/* Folders */}
          {currentFolders.length > 0 && (
            <VStack gap={2}>
              <Text size="sm" tone="secondary">Folders</Text>
              {currentFolders.map(folder => (
                <HStack key={folder.path} gap={3} align="center">
                  <Text>📁</Text>
                  <Button intent="ghost" size="sm" onClick={() => navigate(folder.path)}>
                    {folder.name}
                  </Button>
                  {canDelete && (
                    <Button intent="danger" size="sm" onClick={() => handleDeleteFolder(folder)}>
                      Delete
                    </Button>
                  )}
                </HStack>
              ))}
            </VStack>
          )}

          {/* Files */}
          {currentFiles.length > 0 ? (
            <VStack gap={2}>
              <Text size="sm" tone="secondary">Files</Text>
              {currentFiles.map(file => (
                <HStack key={file.path} gap={3} align="center">
                  <Text>{fileIcon(file.name)}</Text>
                  <VStack gap={0}>
                    <Text size="sm">{file.name}</Text>
                    <HStack gap={2}>
                      <Mono size="xs" tone="secondary">{formatBytes(file.size)}</Mono>
                      {file.modified_at && (
                        <Mono size="xs" tone="secondary">
                          {new Date(file.modified_at).toLocaleString()}
                        </Mono>
                      )}
                    </HStack>
                  </VStack>
                  <HStack gap={2}>
                    {file.url && (
                      <Button
                        intent="soft"
                        size="sm"
                        leftIcon="download"
                        onClick={() => router.get(file.url)}
                      >
                        Download
                      </Button>
                    )}
                    {canDelete && (
                      <Button intent="danger" size="sm" onClick={() => handleDeleteFile(file)}>
                        Delete
                      </Button>
                    )}
                  </HStack>
                </HStack>
              ))}
            </VStack>
          ) : (
            currentFolders.length === 0 && (
              <Text tone="secondary">This folder is empty.</Text>
            )
          )}
        </VStack>
      )}

      {/* Create Folder Modal */}
      <Modal
        open={showFolderModal}
        onClose={() => setShowFolderModal(false)}
        title="New Folder"
        size="sm"
      >
        <VStack gap={4}>
          <Field label="Folder Name" htmlFor="folder-name" required>
            <Input
              id="folder-name"
              value={folderName}
              onChange={e => setFolderName(e.target.value)}
              placeholder="my-folder"
              onKeyDown={e => e.key === 'Enter' && handleCreateFolder()}
            />
          </Field>
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setShowFolderModal(false)}>Cancel</Button>
            <Button intent="primary" loading={creatingFolder} disabled={!folderName.trim()} onClick={handleCreateFolder}>
              Create
            </Button>
          </HStack>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

FileManagerIndex.layout = page => <App title="File Manager">{page}</App>;

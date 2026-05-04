import { useState, useEffect, useCallback, useRef } from 'react';
import {
  IndexPageLayout,
  Button,
  Card, CardContent,
  HStack, VStack,
  Text,
  Input,
  Badge,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

const VIEW_GRID = 'grid';
const VIEW_LIST = 'list';

function formatBytes(bytes) {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDate(ts) {
  if (!ts) return '—';
  return new Date(ts * 1000).toLocaleString();
}

export default function FileManagerIndex() {
  const toast = useToast();
  const fileInputRef = useRef(null);
  const canUpload = useHRMAC('core.file_manager.media_library.upload');
  const canDelete = useHRMAC('core.file_manager.media_library.delete');

  const [currentPath, setCurrentPath] = useState('');
  const [items, setItems] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [search, setSearch] = useState('');
  const [viewMode, setViewMode] = useState(VIEW_GRID);
  const [dragOver, setDragOver] = useState(false);

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const fetchStats = useCallback(async () => {
    try {
      const res = await fetch(route('core.file-manager.stats'), {
        headers: { Accept: 'application/json' },
      });
      if (res.ok) {
        const data = await res.json();
        setStats(data);
      }
    } catch {
      // silently fail stats
    }
  }, []);

  const fetchItems = useCallback(async (path) => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (path) params.append('path', path);
      const res = await fetch(`${route('core.file-manager.browse')}?${params}`, {
        headers: { Accept: 'application/json' },
      });
      if (res.ok) {
        const data = await res.json();
        setItems(data.data || []);
      } else {
        toast.error('Failed to load directory contents');
      }
    } catch {
      toast.error('Network error loading files');
    } finally {
      setLoading(false);
    }
  }, [toast]);

  useEffect(() => {
    fetchStats();
    fetchItems(currentPath);
  }, [currentPath, fetchStats, fetchItems]);

  const navigateTo = (path) => {
    setCurrentPath(path);
    setSearch('');
  };

  const navigateUp = () => {
    if (!currentPath) return;
    const parent = currentPath.includes('/')
      ? currentPath.substring(0, currentPath.lastIndexOf('/'))
      : '';
    navigateTo(parent);
  };

  const handleUpload = async (files) => {
    if (!files || files.length === 0) return;
    setUploading(true);
    const formData = new FormData();
    formData.append('file', files[0]);
    if (currentPath) formData.append('path', currentPath);

    try {
      const res = await fetch(route('core.file-manager.upload'), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf },
        body: formData,
      });
      if (res.ok) {
        toast.success('File uploaded successfully');
        fetchItems(currentPath);
        fetchStats();
      } else {
        const err = await res.json();
        toast.error(err.message || 'Upload failed');
      }
    } catch {
      toast.error('Network error during upload');
    } finally {
      setUploading(false);
    }
  };

  const handleDelete = async (item) => {
    if (!confirm(`Delete ${item.name}?`)) return;
    try {
      const res = await fetch(route('core.file-manager.destroy', item.path), {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
      });
      if (res.ok) {
        toast.success('Deleted successfully');
        fetchItems(currentPath);
        fetchStats();
      } else {
        toast.error('Failed to delete');
      }
    } catch {
      toast.error('Network error');
    }
  };

  const filteredItems = items.filter(item =>
    item.name.toLowerCase().includes(search.toLowerCase())
  );

  const breadcrumbParts = currentPath ? currentPath.split('/').filter(Boolean) : [];

  const renderBreadcrumb = () => (
    <HStack gap={1} align="center" className="text-sm">
      <Button variant="ghost" size="sm" onClick={() => navigateTo('')}>
        Home
      </Button>
      {breadcrumbParts.map((part, idx) => {
        const path = breadcrumbParts.slice(0, idx + 1).join('/');
        return (
          <HStack key={idx} gap={1} align="center">
            <Text className="text-muted">/</Text>
            <Button variant="ghost" size="sm" onClick={() => navigateTo(path)}>
              {part}
            </Button>
          </HStack>
        );
      })}
    </HStack>
  );

  const renderStats = () => (
    <HStack gap={3} className="w-full">
      <Card className="flex-1">
        <CardContent>
          <Text variant="caption">Total Files</Text>
          <Text variant="h3">{stats?.total_files ?? 0}</Text>
        </CardContent>
      </Card>
      <Card className="flex-1">
        <CardContent>
          <Text variant="caption">Total Size</Text>
          <Text variant="h3">{formatBytes(stats?.total_size ?? 0)}</Text>
        </CardContent>
      </Card>
      <Card className="flex-1">
        <CardContent>
          <Text variant="caption">Storage Used</Text>
          <Text variant="h3">{stats?.storage_used_percent ?? '0'}%</Text>
        </CardContent>
      </Card>
    </HStack>
  );

  const renderFolderCard = (folder) => (
    <Card
      key={folder.path}
      className="cursor-pointer hover:bg-muted/50 transition-colors"
      onClick={() => navigateTo(folder.path)}
    >
      <CardContent className="p-4">
        <VStack gap={2} align="center">
          <Text className="text-4xl">📁</Text>
          <Text size="sm" weight="medium" className="text-center truncate w-full">
            {folder.name}
          </Text>
        </VStack>
      </CardContent>
    </Card>
  );

  const renderFileCard = (file) => (
    <Card key={file.path} className="hover:bg-muted/50 transition-colors">
      <CardContent className="p-4">
        <VStack gap={2}>
          <HStack gap={2} justify="between" align="start">
            <Text className="text-4xl">📄</Text>
            {canDelete && (
              <Button
                variant="ghost"
                size="sm"
                onClick={() => handleDelete(file)}
              >
                Delete
              </Button>
            )}
          </HStack>
          <Text size="sm" weight="medium" className="truncate">
            {file.name}
          </Text>
          <Text size="xs" className="text-muted">
            {formatBytes(file.size)} · {formatDate(file.last_modified)}
          </Text>
          {file.url && (
            <a href={file.url} target="_blank" rel="noopener noreferrer" className="text-xs text-primary hover:underline">
              Download
            </a>
          )}
        </VStack>
      </CardContent>
    </Card>
  );

  const renderListRow = (item) => (
    <HStack
      key={item.path}
      gap={3}
      align="center"
      className="py-2 px-3 hover:bg-muted/50 cursor-pointer border-b last:border-b-0"
      onClick={() => item.type === 'directory' ? navigateTo(item.path) : null}
    >
      <Text className="text-xl w-8 text-center">
        {item.type === 'directory' ? '📁' : '📄'}
      </Text>
      <Text size="sm" weight="medium" className="flex-1 truncate">
        {item.name}
      </Text>
      <Text size="xs" className="text-muted w-24 text-right">
        {item.type === 'directory' ? '—' : formatBytes(item.size)}
      </Text>
      <Text size="xs" className="text-muted w-32 text-right">
        {formatDate(item.last_modified)}
      </Text>
      {item.type === 'file' && (
        <HStack gap={1}>
          {item.url && (
            <Button variant="ghost" size="sm" asChild>
              <a href={item.url} target="_blank" rel="noopener noreferrer">Download</a>
            </Button>
          )}
          {canDelete && (
            <Button variant="ghost" size="sm" onClick={(e) => { e.stopPropagation(); handleDelete(item); }}>
              Delete
            </Button>
          )}
        </HStack>
      )}
    </HStack>
  );

  return (
    <IndexPageLayout
      title="File Manager"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'File Manager' },
      ]}
      description="Browse, upload, and manage your files and folders."
    >
      <VStack gap={4} className="w-full">
        {/* Stats */}
        {renderStats()}

        {/* Toolbar */}
        <Card>
          <CardContent>
            <VStack gap={3}>
              {/* Breadcrumb */}
              <HStack gap={2} align="center" justify="between" wrap>
                {renderBreadcrumb()}
                {currentPath && (
                  <Button variant="secondary" size="sm" onClick={navigateUp}>
                    ← Up
                  </Button>
                )}
              </HStack>

              {/* Actions */}
              <HStack gap={2} align="center" justify="between" wrap>
                <HStack gap={2}>
                  <Input
                    placeholder="Search files..."
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                    className="w-64"
                  />
                  <HStack gap={1}>
                    <Button
                      variant={viewMode === VIEW_GRID ? 'primary' : 'secondary'}
                      size="sm"
                      onClick={() => setViewMode(VIEW_GRID)}
                    >
                      Grid
                    </Button>
                    <Button
                      variant={viewMode === VIEW_LIST ? 'primary' : 'secondary'}
                      size="sm"
                      onClick={() => setViewMode(VIEW_LIST)}
                    >
                      List
                    </Button>
                  </HStack>
                </HStack>
                {canUpload && (
                  <HStack gap={2}>
                    <input
                      ref={fileInputRef}
                      type="file"
                      className="hidden"
                      onChange={e => handleUpload(e.target.files)}
                    />
                    <Button
                      variant="primary"
                      size="sm"
                      onClick={() => fileInputRef.current?.click()}
                      disabled={uploading}
                    >
                      {uploading ? 'Uploading...' : 'Upload File'}
                    </Button>
                  </HStack>
                )}
              </HStack>
            </VStack>
          </CardContent>
        </Card>

        {/* Drag & Drop Zone */}
        {canUpload && (
          <div
            className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
              dragOver ? 'border-primary bg-primary/5' : 'border-muted'
            }`}
            onDragOver={e => { e.preventDefault(); setDragOver(true); }}
            onDragLeave={() => setDragOver(false)}
            onDrop={e => {
              e.preventDefault();
              setDragOver(false);
              handleUpload(e.dataTransfer.files);
            }}
          >
            <Text className="text-muted">
              Drag and drop files here to upload
            </Text>
          </div>
        )}

        {/* File Grid / List */}
        {loading ? (
          <Card>
            <CardContent>
              <Text className="text-muted">Loading...</Text>
            </CardContent>
          </Card>
        ) : filteredItems.length === 0 ? (
          <Card>
            <CardContent>
              <Text className="text-muted text-center py-8">
                {search ? 'No files match your search.' : 'This folder is empty.'}
              </Text>
            </CardContent>
          </Card>
        ) : viewMode === VIEW_GRID ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            {filteredItems.map(item =>
              item.type === 'directory' ? renderFolderCard(item) : renderFileCard(item)
            )}
          </div>
        ) : (
          <Card className="overflow-hidden">
            <CardContent className="p-0">
              <HStack
                gap={3}
                align="center"
                className="py-2 px-3 bg-muted/50 border-b font-medium text-sm"
              >
                <Text className="w-8 text-center">Type</Text>
                <Text className="flex-1">Name</Text>
                <Text className="w-24 text-right">Size</Text>
                <Text className="w-32 text-right">Modified</Text>
                <Text className="w-32 text-right">Actions</Text>
              </HStack>
              {filteredItems.map(renderListRow)}
            </CardContent>
          </Card>
        )}
      </VStack>
    </IndexPageLayout>
  );
}

FileManagerIndex.layout = page => (
  <App title="File Manager">{page}</App>
);

import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack, Field, Input, Select,
  Pagination, Modal, Badge, Toggle, Text,
} from '@aero/ui';

const EMPTY_FORM = {
  name:      '',
  type:      'office',
  address:   '',
  city:      '',
  country:   '',
  latitude:  '',
  longitude: '',
  is_active: true,
};

export default function WorkLocationsIndex({ locations, types, filters }) {
  const [search, setSearch]   = useState(filters?.search ?? '');
  const [modalOpen, setModal] = useState(false);
  const [editing, setEditing] = useState(null);

  const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm(EMPTY_FORM);

  const locationTypes = types ?? ['office', 'remote', 'hybrid', 'site'];

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.org.work-locations.index'),
      { ...filters, search: search || undefined, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  function openCreate() {
    clearErrors();
    reset();
    setEditing(null);
    setModal(true);
  }

  function openEdit(row) {
    clearErrors();
    setData({
      name:      row.name      ?? '',
      type:      row.type      ?? 'office',
      address:   row.address   ?? '',
      city:      row.city      ?? '',
      country:   row.country   ?? '',
      latitude:  row.latitude  ?? '',
      longitude: row.longitude ?? '',
      is_active: row.is_active ?? true,
    });
    setEditing(row);
    setModal(true);
  }

  function closeModal() {
    setModal(false);
    setEditing(null);
  }

  function submit(e) {
    e.preventDefault();
    if (editing) {
      put(route('hrm.org.work-locations.update', editing.id), { onSuccess: closeModal });
    } else {
      post(route('hrm.org.work-locations.store'), { onSuccess: closeModal });
    }
  }

  function destroy(row) {
    if (!confirm(`Delete work location "${row.name}"?`)) return;
    router.delete(route('hrm.org.work-locations.destroy', row.id), { preserveScroll: true });
  }

  const columns = [
    { key: 'name',    label: 'Name' },
    {
      key: 'type', label: 'Type',
      render: row => (
        <Badge intent="neutral">{row.type}</Badge>
      ),
    },
    { key: 'city',    label: 'City',    render: row => row.city    ?? <Text tone="secondary">—</Text> },
    { key: 'country', label: 'Country', render: row => row.country ?? <Text tone="secondary">—</Text> },
    {
      key: 'is_active', label: 'Status',
      render: row => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => (
        <HStack gap={1}>
          <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>Edit</Button>
          <Button intent="danger" size="sm" onClick={() => destroy(row)}>Delete</Button>
        </HStack>
      ),
    },
  ];

  const totalPages  = locations.last_page    ?? 1;
  const currentPage = locations.current_page ?? 1;

  return (
    <>
      <IndexPageLayout
        title="Work Locations"
        breadcrumb={[{ label: 'HRM' }, { label: 'Org Structure' }, { label: 'Work Locations' }]}
        actions={
          <Button intent="primary" onClick={openCreate}>
            New Location
          </Button>
        }
        filters={
          <HStack gap={3} wrap>
            <Field label="Search">
              <Input
                value={search}
                onChange={e => setSearch(e.target.value)}
                onKeyDown={e => e.key === 'Enter' && applyFilters()}
                placeholder="Location name or city"
              />
            </Field>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={locations.data ?? []}
            empty="No work locations found."
          />
        }
        pagination={
          totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page => applyFilters({ page })}
            />
          )
        }
      />

      <Modal
        open={modalOpen}
        onClose={closeModal}
        title={editing ? 'Edit Work Location' : 'New Work Location'}
        size="lg"
        footer={
          <HStack gap={2}>
            <Button type="button" intent="primary" loading={processing} onClick={submit}>
              {editing ? 'Save Changes' : 'Create'}
            </Button>
            <Button type="button" intent="ghost" onClick={closeModal}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <form onSubmit={submit}>
          <VStack gap={4}>
            <Field label="Name" error={errors.name} required>
              <Input
                value={data.name}
                onChange={e => setData('name', e.target.value)}
                placeholder="e.g. Dubai HQ"
              />
            </Field>

            <Field label="Type" error={errors.type} required>
              <Select
                value={data.type}
                onChange={e => setData('type', e.target.value)}
              >
                {locationTypes.map(t => (
                  <option key={t} value={t}>
                    {t.charAt(0).toUpperCase() + t.slice(1)}
                  </option>
                ))}
              </Select>
            </Field>

            <Field label="Address" error={errors.address}>
              <Input
                value={data.address}
                onChange={e => setData('address', e.target.value)}
                placeholder="Street address"
              />
            </Field>

            <HStack gap={3}>
              <Field label="City" error={errors.city}>
                <Input
                  value={data.city}
                  onChange={e => setData('city', e.target.value)}
                  placeholder="City"
                />
              </Field>

              <Field label="Country" error={errors.country}>
                <Input
                  value={data.country}
                  onChange={e => setData('country', e.target.value)}
                  placeholder="Country"
                />
              </Field>
            </HStack>

            <HStack gap={3}>
              <Field label="Latitude" error={errors.latitude}>
                <Input
                  type="number"
                  value={data.latitude}
                  onChange={e => setData('latitude', e.target.value)}
                  placeholder="e.g. 25.2048"
                />
              </Field>

              <Field label="Longitude" error={errors.longitude}>
                <Input
                  type="number"
                  value={data.longitude}
                  onChange={e => setData('longitude', e.target.value)}
                  placeholder="e.g. 55.2708"
                />
              </Field>
            </HStack>

            <Toggle
              label="Active"
              checked={data.is_active}
              onChange={e => setData('is_active', e.target.checked)}
            />
          </VStack>
        </form>
      </Modal>
    </>
  );
}

WorkLocationsIndex.layout = page => <App title="Work Locations">{page}</App>;

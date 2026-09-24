import { useState, useEffect, useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Download } from 'lucide-react';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { storiesApi, downloadBlobFromResponse } from '../../api';
import { StoryStatsCards } from './components/StoryStatsCards';
import { StoryFilterBar } from './components/StoryFilterBar';
import { StoryBulkActionsBar } from './components/StoryBulkActionsBar';
import { StoryTable } from './components/StoryTable';

export function LiveStoriesPage() {
  const { showSuccess, showError } = useToast();
  const [searchParams, setSearchParams] = useSearchParams();

  const [stories, setStories] = useState([]);
  const [stats, setStats] = useState({
    totalCount: 0,
    activeCount: 0,
    expiredCount: 0,
  });

  const [search, setSearch] = useState(searchParams.get('q') || '');
  const [mediaType, setMediaType] = useState(searchParams.get('media_type') || '');
  const [dateFrom, setDateFrom] = useState(searchParams.get('date_from') || '');
  const [dateTo, setDateTo] = useState(searchParams.get('date_to') || '');
  const [datePreset, setDatePreset] = useState(searchParams.get('date_preset') || '');
  const [pagination, setPagination] = useState({
    page: parseInt(searchParams.get('page') || '1', 10),
    perPage: 15,
    total: 0,
  });
  const [selectedIds, setSelectedIds] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState(null);

  const fetchLiveStories = useCallback((page = 1) => {
    setLoading(true);
    setError(null);
    const params = {
      page,
      q: search || undefined,
      media_type: mediaType || undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
    };

    storiesApi
      .getLiveStories(params)
      .then((res) => {
        const items = res?.stories?.data || res?.data || (Array.isArray(res) ? res : []);
        const paginator = res?.stories || {};

        setStories(items);
        setStats({
          totalCount: res?.totalCount ?? paginator?.total ?? items.length,
          activeCount: res?.activeCount ?? 0,
          expiredCount: res?.expiredCount ?? 0,
        });
        setPagination({
          page: paginator?.current_page || page,
          perPage: paginator?.per_page || 15,
          total: paginator?.total || items.length,
        });
        setSelectedIds([]);
      })
      .catch((err) => {
        setError(err.message || 'Failed to load live stories.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [search, mediaType, dateFrom, dateTo]);

  useEffect(() => {
    fetchLiveStories(pagination.page);
  }, [fetchLiveStories, pagination.page]);

  const handleSearchChange = (val) => {
    setSearch(val);
    setPagination((prev) => ({ ...prev, page: 1 }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      if (val) next.set('q', val);
      else next.delete('q');
      next.set('page', '1');
      return next;
    });
  };

  const handleMediaTypeChange = (val) => {
    setMediaType(val);
    setPagination((prev) => ({ ...prev, page: 1 }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      if (val) next.set('media_type', val);
      else next.delete('media_type');
      next.set('page', '1');
      return next;
    });
  };

  const handleDateChange = ({ startDate, endDate, preset }) => {
    setDateFrom(startDate || '');
    setDateTo(endDate || '');
    setDatePreset(preset || '');
    setPagination((prev) => ({ ...prev, page: 1 }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      if (startDate) next.set('date_from', startDate);
      else next.delete('date_from');
      if (endDate) next.set('date_to', endDate);
      else next.delete('date_to');
      if (preset && preset !== 'all' && preset !== 'custom') next.set('date_preset', preset);
      else next.delete('date_preset');
      next.set('page', '1');
      return next;
    });
  };

  const handleDateReset = () => {
    setDateFrom('');
    setDateTo('');
    setDatePreset('');
    setPagination((prev) => ({ ...prev, page: 1 }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.delete('date_from');
      next.delete('date_to');
      next.delete('date_preset');
      next.set('page', '1');
      return next;
    });
  };

  const handlePageChange = (page) => {
    setPagination((prev) => ({ ...prev, page }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.set('page', String(page));
      return next;
    });
  };

  const handleDelete = (story) => {
    confirmHelper.confirmDelete({
      itemName: `Live Story #${story.id} by ${story.member?.name || story.author?.name || 'Member'}`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await storiesApi.deleteStory(story.id);
          showSuccess('Story deleted successfully.');
          fetchLiveStories(pagination.page);
        } catch (err) {
          showError(err.message || 'Failed to delete story.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleBulkDelete = () => {
    if (!selectedIds.length) return;
    confirmHelper.confirm({
      header: 'Bulk Delete Stories',
      message: `Are you sure you want to permanently delete ${selectedIds.length} selected story/stories?`,
      icon: 'pi pi-exclamation-triangle text-amber-500',
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await storiesApi.bulkAction('delete', selectedIds);
          showSuccess('Selected stories deleted successfully.');
          fetchLiveStories(pagination.page);
        } catch (err) {
          showError(err.message || 'Failed to delete selected stories.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleExport = async () => {
    if (exporting) return;
    setExporting(true);
    try {
      const params = {
        status: 'active',
        ...(search ? { q: search } : {}),
        ...(mediaType ? { media_type: mediaType } : {}),
        ...(dateFrom ? { date_from: dateFrom } : {}),
        ...(dateTo ? { date_to: dateTo } : {}),
      };
      const blob = await storiesApi.exportCsv(params);
      await downloadBlobFromResponse(blob, 'live-stories-export.csv');
      showSuccess('Stories CSV export downloaded.');
    } catch (err) {
      showError(err.message || 'Failed to export CSV.');
    } finally {
      setExporting(false);
    }
  };

  const toggleSelectAll = (checked) => {
    if (checked) {
      setSelectedIds(stories.map((s) => s.id));
    } else {
      setSelectedIds([]);
    }
  };

  const toggleSelectOne = (id) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]
    );
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Live Stories"
        subtitle="Monitor active 24-hour ephemeral stories and real-time user engagement."
        breadcrumbs={[
          { label: 'Stories Management', to: '/admin/stories' },
          { label: 'Live Stories' },
        ]}
        actions={
          <Button
            label={exporting ? 'Exporting...' : 'Export CSV'}
            icon={<Download className="w-3.5 h-3.5 mr-1.5" />}
            onClick={handleExport}
            disabled={exporting}
            loading={exporting}
            className="p-button-outlined p-button-secondary text-xs"
          />
        }
      />

      {/* Stats Cards */}
      <StoryStatsCards
        totalCount={stats.totalCount}
        activeCount={stats.activeCount}
        expiredCount={stats.expiredCount}
        currentMode="live"
      />

      {/* Filter Bar */}
      <StoryFilterBar
        search={search}
        onSearchChange={handleSearchChange}
        showStatusFilter={false}
        mediaType={mediaType}
        onMediaTypeChange={handleMediaTypeChange}
        dateFrom={dateFrom}
        dateTo={dateTo}
        datePreset={datePreset}
        onDateChange={handleDateChange}
        onDateReset={handleDateReset}
        onRefresh={() => fetchLiveStories(pagination.page)}
        loading={loading}
      />

      {/* Bulk Delete Bar */}
      <StoryBulkActionsBar
        selectedCount={selectedIds.length}
        onBulkDelete={handleBulkDelete}
        loading={actionLoading}
      />

      {/* Table */}
      <StoryTable
        stories={stories}
        loading={loading}
        error={error}
        pagination={pagination}
        onPageChange={handlePageChange}
        selectedIds={selectedIds}
        onToggleSelectAll={toggleSelectAll}
        onToggleSelectOne={toggleSelectOne}
        onDeleteStory={handleDelete}
        onRetry={() => fetchLiveStories(1)}
        emptyTitle="No Live Stories Found"
        emptyMessage="There are currently no active/live stories published by platform members."
      />
    </div>
  );
}

export default LiveStoriesPage;

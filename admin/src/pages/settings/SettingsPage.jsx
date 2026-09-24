import { useState, useEffect, useCallback } from 'react';
import { TabView, TabPanel } from 'primereact/tabview';
import { PageHeader } from '../../components/common/PageHeader';
import { ErrorState } from '../../components/common/ErrorState';
import { useToast } from '../../hooks/useToast';
import { useBranding } from '../../hooks/useBranding';
import { settingsApi } from '../../api';

// Subcomponents
import { GeneralSettingsTab } from './components/GeneralSettingsTab';
import { BrandingSettingsTab } from './components/BrandingSettingsTab';
import { ContactSettingsTab } from './components/ContactSettingsTab';
import { MailStorageTab } from './components/MailStorageTab';
import { CacheMaintenanceTab } from './components/CacheMaintenanceTab';
import { SeoSocialTab } from './components/SeoSocialTab';
import { SystemDiagnosticsTab } from './components/SystemDiagnosticsTab';
import { SettingsSkeleton } from './components/SettingsSkeleton';

export function SettingsPage() {
  const { showSuccess, showError } = useToast();
  const { updateBranding } = useBranding();

  const [settings, setSettings] = useState({});
  const [systemInfo, setSystemInfo] = useState({});
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState(0);

  const fetchSettings = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await settingsApi.getSettings();
      setSettings(res?.settings || res?.data?.settings || res?.data || {});
      setSystemInfo(res?.systemInfo || res?.data?.systemInfo || {});
    } catch (err) {
      setError(err.message || 'Failed to load platform settings.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    let isMounted = true;
    settingsApi.getSettings()
      .then((res) => {
        if (!isMounted) return;
        setSettings(res?.settings || res?.data?.settings || res?.data || {});
        setSystemInfo(res?.systemInfo || res?.data?.systemInfo || {});
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load platform settings.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleSaveSettings = async (settingsData, files = {}) => {
    setActionLoading(true);
    try {
      const res = await settingsApi.updateSettings(settingsData, files);
      showSuccess('Platform settings updated successfully.');
      if (res?.branding) {
        updateBranding(res.branding);
      } else if (res?.settings) {
        updateBranding(res.settings);
      }
      if (res?.settings) {
        setSettings(res.settings);
      }
      await fetchSettings();
      return res;
    } catch (err) {
      showError(err.message || 'Failed to update settings.');
      throw err;
    } finally {
      setActionLoading(false);
    }
  };

  const handleClearCache = async (type = 'all') => {
    setActionLoading(true);
    try {
      await settingsApi.clearCache(type);
      showSuccess(`Application cache (${type}) cleared successfully.`);
      fetchSettings();
    } catch (err) {
      showError(err.message || 'Failed to clear cache.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleToggleMaintenance = async (action, secret = '') => {
    setActionLoading(true);
    try {
      await settingsApi.toggleMaintenance(action, secret);
      showSuccess(`Platform maintenance mode ${action === 'up' ? 'disabled' : 'enabled'}.`);
      fetchSettings();
    } catch (err) {
      showError(err.message || 'Failed to toggle maintenance mode.');
    } finally {
      setActionLoading(false);
    }
  };

  if (loading && Object.keys(settings).length === 0) {
    return <SettingsSkeleton />;
  }

  if (error && Object.keys(settings).length === 0) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Platform Settings"
          breadcrumbs={[{ label: 'Settings' }]}
        />
        <ErrorState
          title="Settings Unavailable"
          message={error}
          onRetry={fetchSettings}
        />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Platform Settings & Configuration Center"
        subtitle="Manage global application settings, branding assets, cache, and system diagnostics."
        breadcrumbs={[{ label: 'System', to: '/admin/settings' }, { label: 'Settings Center' }]}
      />

      {/* Main Settings Tabbed Card */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6">
        <TabView activeIndex={activeTab} onTabChange={(e) => setActiveTab(e.index)}>
          {/* Tab 1: General Settings */}
          <TabPanel header="General Settings">
            <GeneralSettingsTab
              initialValues={settings}
              onSave={handleSaveSettings}
              loading={actionLoading}
            />
          </TabPanel>

          {/* Tab 2: Branding & Assets */}
          <TabPanel header="Branding & Assets">
            <BrandingSettingsTab
              initialValues={settings}
              onUpload={handleSaveSettings}
              loading={actionLoading}
            />
          </TabPanel>

          {/* Tab 3: Contact Info */}
          <TabPanel header="Contact Info">
            <ContactSettingsTab
              initialValues={settings}
              onSave={handleSaveSettings}
              loading={actionLoading}
            />
          </TabPanel>

          {/* Tab 4: Mail & Storage */}
          <TabPanel header="Mail & Storage">
            <MailStorageTab systemInfo={systemInfo} />
          </TabPanel>

          {/* Tab 5: Cache & Maintenance */}
          <TabPanel header="Cache & Maintenance">
            <CacheMaintenanceTab
              systemInfo={systemInfo}
              onClearCache={handleClearCache}
              onToggleMaintenance={handleToggleMaintenance}
              loading={actionLoading}
            />
          </TabPanel>

          {/* Tab 6: SEO & Social */}
          <TabPanel header="SEO & Social">
            <SeoSocialTab
              initialValues={settings}
              onSave={handleSaveSettings}
              loading={actionLoading}
            />
          </TabPanel>

          {/* Tab 7: System Diagnostics */}
          <TabPanel header="System Diagnostics">
            <SystemDiagnosticsTab systemInfo={systemInfo} />
          </TabPanel>
        </TabView>
      </div>
    </div>
  );
}

export default SettingsPage;

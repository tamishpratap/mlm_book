import React, { useState, useEffect, useRef } from 'react';
import {
  Volume2,
  VolumeX,
  Play,
  Square,
  Upload,
  RefreshCw,
  RotateCcw,
  CheckCircle2,
  AlertCircle,
  Music,
  Users,
  Shield,
  FileAudio,
  Trash2,
  Info,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { notificationsApi } from '../../api';
import { setCustomNotificationSoundUrl } from '../../utils/notificationSound';

const ACCEPTED_EXTENSIONS = ['.mp3', '.wav', '.ogg', '.aac', '.m4a'];
const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB

export function NotificationSoundPage() {
  const { showSuccess, showError, showInfo } = useToast();

  // Settings State
  const [loading, setLoading] = useState(true);
  const [settings, setSettings] = useState({
    admin_sound: {
      path: 'sounds/notification.mp3',
      url: '/sounds/notification.mp3',
      filename: 'notification.mp3 (Default)',
      is_custom: false,
    },
    member_sound: {
      path: 'sounds/notification.mp3',
      url: '/sounds/notification.mp3',
      filename: 'notification.mp3 (Default)',
      is_custom: false,
    },
  });

  // Staged Files & Previews
  const [memberFile, setMemberFile] = useState(null);
  const [memberPreviewUrl, setMemberPreviewUrl] = useState(null);
  const memberInputRef = useRef(null);

  const [adminFile, setAdminFile] = useState(null);
  const [adminPreviewUrl, setAdminPreviewUrl] = useState(null);
  const adminInputRef = useRef(null);

  // Independent Loading States
  const [savingMember, setSavingMember] = useState(false);
  const [savingAdmin, setSavingAdmin] = useState(false);
  const [savingAll, setSavingAll] = useState(false);

  // Audio Playback State (Singleton player for previews)
  const [playingKey, setPlayingKey] = useState(null); // 'member_current' | 'member_new' | 'admin_current' | 'admin_new'
  const activeAudioRef = useRef(null);

  // Stop currently playing preview
  const stopAudio = () => {
    if (activeAudioRef.current) {
      activeAudioRef.current.pause();
      activeAudioRef.current.currentTime = 0;
      activeAudioRef.current = null;
    }
    setPlayingKey(null);
  };

  // Play preview for given source
  const playAudio = (key, src) => {
    if (!src) return;

    if (playingKey === key) {
      stopAudio();
      return;
    }

    stopAudio();

    try {
      const audio = new Audio(src);
      activeAudioRef.current = audio;
      setPlayingKey(key);

      audio.onended = () => {
        setPlayingKey(null);
        activeAudioRef.current = null;
      };

      audio.onerror = () => {
        showError('Unable to preview audio file. Format may not be supported by browser.');
        setPlayingKey(null);
        activeAudioRef.current = null;
      };

      const playPromise = audio.play();
      if (playPromise !== undefined && typeof playPromise.catch === 'function') {
        playPromise.catch((err) => {
          console.warn('[Audio Preview Error]', err);
          showError('Playback blocked or unsupported format.');
          setPlayingKey(null);
          activeAudioRef.current = null;
        });
      }
    } catch (err) {
      showError('Audio playback initialization failed.');
      setPlayingKey(null);
    }
  };

  // Fetch initial settings
  const fetchSettings = async () => {
    setLoading(true);
    try {
      const res = await notificationsApi.getSoundSettings();
      if (res?.success && res?.settings) {
        setSettings(res.settings);
        if (res.settings.admin_sound?.url) {
          setCustomNotificationSoundUrl(res.settings.admin_sound.url);
        }
      }
    } catch (err) {
      showError(err.message || 'Failed to retrieve notification sound settings.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSettings();

    return () => {
      stopAudio();
      if (memberPreviewUrl && memberPreviewUrl.startsWith('blob:')) {
        URL.revokeObjectURL(memberPreviewUrl);
      }
      if (adminPreviewUrl && adminPreviewUrl.startsWith('blob:')) {
        URL.revokeObjectURL(adminPreviewUrl);
      }
    };
  }, []);

  // Validate Audio File
  const validateAudioFile = (file) => {
    if (!file) return false;

    const ext = '.' + file.name.split('.').pop().toLowerCase();
    if (!ACCEPTED_EXTENSIONS.includes(ext)) {
      showError(`Unsupported file format. Please upload one of: ${ACCEPTED_EXTENSIONS.join(', ')}`);
      return false;
    }

    if (file.size > MAX_FILE_SIZE_BYTES) {
      showError('File size exceeds the 10 MB limit. Please select a smaller sound file.');
      return false;
    }

    return true;
  };

  // Member File Change Handlers
  const handleMemberFileSelect = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (!validateAudioFile(file)) {
      e.target.value = '';
      return;
    }

    if (memberPreviewUrl && memberPreviewUrl.startsWith('blob:')) {
      URL.revokeObjectURL(memberPreviewUrl);
    }

    setMemberFile(file);
    setMemberPreviewUrl(URL.createObjectURL(file));
  };

  const handleCancelMemberSelection = () => {
    if (playingKey === 'member_new') stopAudio();
    if (memberPreviewUrl && memberPreviewUrl.startsWith('blob:')) {
      URL.revokeObjectURL(memberPreviewUrl);
    }
    setMemberFile(null);
    setMemberPreviewUrl(null);
    if (memberInputRef.current) memberInputRef.current.value = '';
  };

  // Admin File Change Handlers
  const handleAdminFileSelect = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (!validateAudioFile(file)) {
      e.target.value = '';
      return;
    }

    if (adminPreviewUrl && adminPreviewUrl.startsWith('blob:')) {
      URL.revokeObjectURL(adminPreviewUrl);
    }

    setAdminFile(file);
    setAdminPreviewUrl(URL.createObjectURL(file));
  };

  const handleCancelAdminSelection = () => {
    if (playingKey === 'admin_new') stopAudio();
    if (adminPreviewUrl && adminPreviewUrl.startsWith('blob:')) {
      URL.revokeObjectURL(adminPreviewUrl);
    }
    setAdminFile(null);
    setAdminPreviewUrl(null);
    if (adminInputRef.current) adminInputRef.current.value = '';
  };

  // Save Member Sound
  const handleSaveMemberSound = async () => {
    if (!memberFile) {
      showInfo('Please select an audio file to upload for member notifications.');
      return;
    }

    setSavingMember(true);
    stopAudio();

    try {
      const formData = new FormData();
      formData.append('member_sound', memberFile);

      const res = await notificationsApi.updateSoundSettings(formData);
      if (res?.success) {
        showSuccess(res.message || 'Member notification sound updated successfully.');
        if (res.settings) {
          setSettings(res.settings);
        }
        handleCancelMemberSelection();
      } else {
        showError(res?.message || 'Failed to update member notification sound.');
      }
    } catch (err) {
      showError(err.message || 'Failed to upload member sound.');
    } finally {
      setSavingMember(false);
    }
  };

  // Reset Member Sound to Default
  const handleResetMemberSound = () => {
    confirmHelper.confirm({
      header: 'Reset Member Notification Sound',
      message: 'Are you sure you want to reset the Member notification sound to the system default?',
      icon: 'pi pi-exclamation-triangle',
      onAccept: async () => {
        setSavingMember(true);
        stopAudio();
        try {
          const formData = new FormData();
          formData.append('reset_member_sound', '1');

          const res = await notificationsApi.updateSoundSettings(formData);
          if (res?.success) {
            showSuccess(res.message || 'Member notification sound reset to default.');
            if (res.settings) {
              setSettings(res.settings);
            }
            handleCancelMemberSelection();
          }
        } catch (err) {
          showError(err.message || 'Failed to reset member notification sound.');
        } finally {
          setSavingMember(false);
        }
      },
    });
  };

  // Save Admin Sound
  const handleSaveAdminSound = async () => {
    if (!adminFile) {
      showInfo('Please select an audio file to upload for admin notifications.');
      return;
    }

    setSavingAdmin(true);
    stopAudio();

    try {
      const formData = new FormData();
      formData.append('admin_sound', adminFile);

      const res = await notificationsApi.updateSoundSettings(formData);
      if (res?.success) {
        showSuccess(res.message || 'Admin notification sound updated successfully.');
        if (res.settings) {
          setSettings(res.settings);
          if (res.settings.admin_sound?.url) {
            setCustomNotificationSoundUrl(res.settings.admin_sound.url);
          }
        }
        handleCancelAdminSelection();
      } else {
        showError(res?.message || 'Failed to update admin notification sound.');
      }
    } catch (err) {
      showError(err.message || 'Failed to upload admin sound.');
    } finally {
      setSavingAdmin(false);
    }
  };

  // Reset Admin Sound to Default
  const handleResetAdminSound = () => {
    confirmHelper.confirm({
      header: 'Reset Admin Notification Sound',
      message: 'Are you sure you want to reset the Admin notification sound to the system default?',
      icon: 'pi pi-exclamation-triangle',
      onAccept: async () => {
        setSavingAdmin(true);
        stopAudio();
        try {
          const formData = new FormData();
          formData.append('reset_admin_sound', '1');

          const res = await notificationsApi.updateSoundSettings(formData);
          if (res?.success) {
            showSuccess(res.message || 'Admin notification sound reset to default.');
            if (res.settings) {
              setSettings(res.settings);
              if (res.settings.admin_sound?.url) {
                setCustomNotificationSoundUrl(res.settings.admin_sound.url);
              }
            }
            handleCancelAdminSelection();
          }
        } catch (err) {
          showError(err.message || 'Failed to reset admin notification sound.');
        } finally {
          setSavingAdmin(false);
        }
      },
    });
  };

  // Save Both Sounds Simultaneously
  const handleSaveAllChanges = async () => {
    if (!memberFile && !adminFile) {
      showInfo('No pending audio file selections to save.');
      return;
    }

    setSavingAll(true);
    stopAudio();

    try {
      const formData = new FormData();
      if (memberFile) formData.append('member_sound', memberFile);
      if (adminFile) formData.append('admin_sound', adminFile);

      const res = await notificationsApi.updateSoundSettings(formData);
      if (res?.success) {
        showSuccess(res.message || 'Notification sound settings saved successfully.');
        if (res.settings) {
          setSettings(res.settings);
          if (res.settings.admin_sound?.url) {
            setCustomNotificationSoundUrl(res.settings.admin_sound.url);
          }
        }
        handleCancelMemberSelection();
        handleCancelAdminSelection();
      } else {
        showError(res?.message || 'Failed to save sound settings.');
      }
    } catch (err) {
      showError(err.message || 'Failed to save sound settings.');
    } finally {
      setSavingAll(false);
    }
  };

  const hasAnyPendingChanges = Boolean(memberFile || adminFile);

  return (
    <div className="space-y-6 max-w-7xl mx-auto pb-12">
      {/* Page Header */}
      <PageHeader
        title="Change Notification Sound"
        subtitle="Manage and customize the alert chimes for Member and Admin portal notifications independently."
        breadcrumbs={[
          { label: 'Notifications', to: '/admin/notifications' },
          { label: 'Change Notification Sound' },
        ]}
      >
        <div className="flex items-center gap-3">
          <Button
            type="button"
            icon={<RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />}
            label="Refresh"
            className="p-button-outlined p-button-sm !border-slate-300 !text-slate-700 hover:!bg-slate-100"
            onClick={fetchSettings}
            disabled={loading || savingMember || savingAdmin || savingAll}
          />
          {hasAnyPendingChanges && (
            <Button
              type="button"
              icon={<CheckCircle2 className="w-4 h-4 mr-2" />}
              label={savingAll ? 'Saving All...' : 'Save All Changes'}
              className="p-button-sm !bg-indigo-600 hover:!bg-indigo-700 !border-indigo-600 !text-white"
              onClick={handleSaveAllChanges}
              disabled={savingAll || savingMember || savingAdmin}
              loading={savingAll}
            />
          )}
        </div>
      </PageHeader>

      {/* Main Two-Column Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* ======================================================== */}
        {/* 1. MEMBER NOTIFICATION SOUND CARD                        */}
        {/* ======================================================== */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between">
          <div className="p-6 space-y-6">
            {/* Header */}
            <div className="flex items-start justify-between border-b border-slate-100 pb-4">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                  <Users className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="text-base font-bold text-slate-900">Member Notification Sound</h3>
                  <p className="text-xs text-slate-500 mt-0.5">
                    Triggered when members receive community, transaction, or account alerts.
                  </p>
                </div>
              </div>
              <span
                className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                  settings.member_sound.is_custom
                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                    : 'bg-slate-100 text-slate-600 border border-slate-200'
                }`}
              >
                {settings.member_sound.is_custom ? 'Custom Sound' : 'Default System'}
              </span>
            </div>

            {/* Currently Active Sound Section */}
            <div className="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-slate-600 uppercase tracking-wider">
                  Currently Active Sound
                </span>
                {settings.member_sound.is_custom && (
                  <button
                    type="button"
                    onClick={handleResetMemberSound}
                    disabled={savingMember || savingAll}
                    className="inline-flex items-center text-xs font-semibold text-rose-600 hover:text-rose-700 hover:underline transition-colors disabled:opacity-50"
                  >
                    <RotateCcw className="w-3.5 h-3.5 mr-1" />
                    Reset to Default
                  </button>
                )}
              </div>

              <div className="flex items-center justify-between bg-white p-3 rounded-lg border border-slate-200">
                <div className="flex items-center gap-3 min-w-0">
                  <FileAudio className="w-5 h-5 text-blue-500 shrink-0" />
                  <div className="truncate">
                    <p className="text-sm font-medium text-slate-800 truncate">
                      {settings.member_sound.filename}
                    </p>
                    <p className="text-xs text-slate-400">
                      {settings.member_sound.is_custom ? 'Custom uploaded audio' : 'Built-in platform chime'}
                    </p>
                  </div>
                </div>

                <Button
                  type="button"
                  icon={
                    playingKey === 'member_current' ? (
                      <Square className="w-4 h-4 fill-current text-white" />
                    ) : (
                      <Play className="w-4 h-4 fill-current ml-0.5 text-blue-600" />
                    )
                  }
                  label={playingKey === 'member_current' ? 'Stop' : 'Preview'}
                  className={`p-button-sm !text-xs !py-1.5 !px-3 ${
                    playingKey === 'member_current'
                      ? '!bg-rose-600 !border-rose-600 !text-white'
                      : 'p-button-outlined !border-blue-300 hover:!bg-blue-50 !text-blue-700'
                  }`}
                  onClick={() => playAudio('member_current', settings.member_sound.url)}
                  disabled={loading}
                />
              </div>
            </div>

            {/* Upload or Replace Section */}
            <div className="space-y-3">
              <label className="text-xs font-bold text-slate-700 block">
                {settings.member_sound.is_custom ? 'Replace Member Sound' : 'Upload New Member Sound'}
              </label>

              {!memberFile ? (
                <div
                  onClick={() => memberInputRef.current?.click()}
                  className="border-2 border-dashed border-slate-200 hover:border-blue-400 hover:bg-blue-50/30 rounded-xl p-6 text-center cursor-pointer transition-all group"
                >
                  <input
                    ref={memberInputRef}
                    type="file"
                    accept=".mp3,.wav,.ogg,.aac,.m4a"
                    className="hidden"
                    onChange={handleMemberFileSelect}
                  />
                  <div className="w-12 h-12 rounded-full bg-blue-50 group-hover:bg-blue-100 text-blue-600 mx-auto flex items-center justify-center transition-colors">
                    <Upload className="w-6 h-6" />
                  </div>
                  <p className="mt-3 text-sm font-semibold text-slate-800">
                    Click to select or drag audio file here
                  </p>
                  <p className="text-xs text-slate-500 mt-1">
                    Supports MP3, WAV, OGG, AAC, M4A up to 10 MB
                  </p>
                </div>
              ) : (
                <div className="p-4 rounded-xl bg-blue-50/70 border border-blue-200 space-y-3">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-blue-900 uppercase tracking-wider flex items-center gap-1.5">
                      <Music className="w-3.5 h-3.5" />
                      Selected File (Pending Save)
                    </span>
                    <button
                      type="button"
                      onClick={handleCancelMemberSelection}
                      className="text-slate-400 hover:text-slate-600 transition-colors"
                      title="Cancel selection"
                    >
                      <Trash2 className="w-4 h-4 text-rose-500" />
                    </button>
                  </div>

                  <div className="flex items-center justify-between bg-white p-3 rounded-lg border border-blue-200">
                    <div className="truncate pr-3">
                      <p className="text-sm font-bold text-slate-900 truncate">{memberFile.name}</p>
                      <p className="text-xs text-slate-500">
                        {(memberFile.size / 1024).toFixed(1)} KB • Ready to upload
                      </p>
                    </div>

                    <div className="flex items-center gap-2">
                      <Button
                        type="button"
                        icon={
                          playingKey === 'member_new' ? (
                            <Square className="w-4 h-4 fill-current text-white" />
                          ) : (
                            <Play className="w-4 h-4 fill-current ml-0.5 text-blue-600" />
                          )
                        }
                        label={playingKey === 'member_new' ? 'Stop' : 'Listen'}
                        className={`p-button-sm !text-xs !py-1.5 !px-3 ${
                          playingKey === 'member_new'
                            ? '!bg-rose-600 !border-rose-600 !text-white'
                            : 'p-button-outlined !border-blue-300 hover:!bg-blue-50 !text-blue-700'
                        }`}
                        onClick={() => playAudio('member_new', memberPreviewUrl)}
                      />
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Member Card Footer Actions */}
          <div className="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
            <span className="text-xs text-slate-500">
              {memberFile ? 'Unsaved changes pending' : 'No changes staged'}
            </span>
            <div className="flex items-center gap-2">
              {memberFile && (
                <Button
                  type="button"
                  label="Cancel"
                  className="p-button-text p-button-sm !text-slate-600 hover:!bg-slate-200"
                  onClick={handleCancelMemberSelection}
                  disabled={savingMember || savingAll}
                />
              )}
              <Button
                type="button"
                icon={<Upload className="w-4 h-4 mr-2" />}
                label={savingMember ? 'Saving...' : 'Save Member Sound'}
                className="p-button-sm !bg-blue-600 hover:!bg-blue-700 !border-blue-600 !text-white"
                onClick={handleSaveMemberSound}
                disabled={!memberFile || savingMember || savingAll}
                loading={savingMember}
              />
            </div>
          </div>
        </div>

        {/* ======================================================== */}
        {/* 2. ADMIN NOTIFICATION SOUND CARD                         */}
        {/* ======================================================== */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between">
          <div className="p-6 space-y-6">
            {/* Header */}
            <div className="flex items-start justify-between border-b border-slate-100 pb-4">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                  <Shield className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="text-base font-bold text-slate-900">Admin Notification Sound</h3>
                  <p className="text-xs text-slate-500 mt-0.5">
                    Triggered for admin alerts, system notifications, and moderation actions.
                  </p>
                </div>
              </div>
              <span
                className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                  settings.admin_sound.is_custom
                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                    : 'bg-slate-100 text-slate-600 border border-slate-200'
                }`}
              >
                {settings.admin_sound.is_custom ? 'Custom Sound' : 'Default System'}
              </span>
            </div>

            {/* Currently Active Sound Section */}
            <div className="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-slate-600 uppercase tracking-wider">
                  Currently Active Sound
                </span>
                {settings.admin_sound.is_custom && (
                  <button
                    type="button"
                    onClick={handleResetAdminSound}
                    disabled={savingAdmin || savingAll}
                    className="inline-flex items-center text-xs font-semibold text-rose-600 hover:text-rose-700 hover:underline transition-colors disabled:opacity-50"
                  >
                    <RotateCcw className="w-3.5 h-3.5 mr-1" />
                    Reset to Default
                  </button>
                )}
              </div>

              <div className="flex items-center justify-between bg-white p-3 rounded-lg border border-slate-200">
                <div className="flex items-center gap-3 min-w-0">
                  <FileAudio className="w-5 h-5 text-purple-500 shrink-0" />
                  <div className="truncate">
                    <p className="text-sm font-medium text-slate-800 truncate">
                      {settings.admin_sound.filename}
                    </p>
                    <p className="text-xs text-slate-400">
                      {settings.admin_sound.is_custom ? 'Custom uploaded audio' : 'Built-in platform chime'}
                    </p>
                  </div>
                </div>

                <Button
                  type="button"
                  icon={
                    playingKey === 'admin_current' ? (
                      <Square className="w-4 h-4 fill-current text-white" />
                    ) : (
                      <Play className="w-4 h-4 fill-current ml-0.5 text-purple-600" />
                    )
                  }
                  label={playingKey === 'admin_current' ? 'Stop' : 'Preview'}
                  className={`p-button-sm !text-xs !py-1.5 !px-3 ${
                    playingKey === 'admin_current'
                      ? '!bg-rose-600 !border-rose-600 !text-white'
                      : 'p-button-outlined !border-purple-300 hover:!bg-purple-50 !text-purple-700'
                  }`}
                  onClick={() => playAudio('admin_current', settings.admin_sound.url)}
                  disabled={loading}
                />
              </div>
            </div>

            {/* Upload or Replace Section */}
            <div className="space-y-3">
              <label className="text-xs font-bold text-slate-700 block">
                {settings.admin_sound.is_custom ? 'Replace Admin Sound' : 'Upload New Admin Sound'}
              </label>

{!adminFile ? (
                <div
                  onClick={() => adminInputRef.current?.click()}
                  className="border-2 border-dashed border-slate-200 hover:border-purple-400 hover:bg-purple-50/30 rounded-xl p-6 text-center cursor-pointer transition-all group"
                >
                  <input
                    ref={adminInputRef}
                    type="file"
                    accept=".mp3,.wav,.ogg,.aac,.m4a"
                    className="hidden"
                    onChange={handleAdminFileSelect}
                  />
                  <div className="w-12 h-12 rounded-full bg-purple-50 group-hover:bg-purple-100 text-purple-600 mx-auto flex items-center justify-center transition-colors">
                    <Upload className="w-6 h-6" />
                  </div>
                  <p className="mt-3 text-sm font-semibold text-slate-800">
                    Click to select or drag audio file here
                  </p>
                  <p className="text-xs text-slate-500 mt-1">
                    Supports MP3, WAV, OGG, AAC, M4A up to 10 MB
                  </p>
                </div>
              ) : (
                <div className="p-4 rounded-xl bg-purple-50/70 border border-purple-200 space-y-3">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-purple-900 uppercase tracking-wider flex items-center gap-1.5">
                      <Music className="w-3.5 h-3.5" />
                      Selected File (Pending Save)
                    </span>
                    <button
                      type="button"
                      onClick={handleCancelAdminSelection}
                      className="text-slate-400 hover:text-slate-600 transition-colors"
                      title="Cancel selection"
                    >
                      <Trash2 className="w-4 h-4 text-rose-500" />
                    </button>
                  </div>

                  <div className="flex items-center justify-between bg-white p-3 rounded-lg border border-purple-200">
                    <div className="truncate pr-3">
                      <p className="text-sm font-bold text-slate-900 truncate">{adminFile.name}</p>
                      <p className="text-xs text-slate-500">
                        {(adminFile.size / 1024).toFixed(1)} KB • Ready to upload
                      </p>
                    </div>

                    <div className="flex items-center gap-2">
                      <Button
                        type="button"
                        icon={
                          playingKey === 'admin_new' ? (
                            <Square className="w-4 h-4 fill-current text-white" />
                          ) : (
                            <Play className="w-4 h-4 fill-current ml-0.5 text-purple-600" />
                          )
                        }
                        label={playingKey === 'admin_new' ? 'Stop' : 'Listen'}
                        className={`p-button-sm !text-xs !py-1.5 !px-3 ${
                          playingKey === 'admin_new'
                            ? '!bg-rose-600 !border-rose-600 !text-white'
                            : 'p-button-outlined !border-purple-300 hover:!bg-purple-50 !text-purple-700'
                        }`}
                        onClick={() => playAudio('admin_new', adminPreviewUrl)}
                      />
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Admin Card Footer Actions */}
          <div className="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
            <span className="text-xs text-slate-500">
              {adminFile ? 'Unsaved changes pending' : 'No changes staged'}
            </span>
            <div className="flex items-center gap-2">
              {adminFile && (
                <Button
                  type="button"
                  label="Cancel"
                  className="p-button-text p-button-sm !text-slate-600 hover:!bg-slate-200"
                  onClick={handleCancelAdminSelection}
                  disabled={savingAdmin || savingAll}
                />
              )}
              <Button
                type="button"
                icon={<Upload className="w-4 h-4 mr-2" />}
                label={savingAdmin ? 'Saving...' : 'Save Admin Sound'}
                className="p-button-sm !bg-purple-600 hover:!bg-purple-700 !border-purple-600 !text-white"
                onClick={handleSaveAdminSound}
                disabled={!adminFile || savingAdmin || savingAll}
                loading={savingAdmin}
              />
            </div>
          </div>
        </div>
      </div>

      {/* Guidelines & Information Panel */}
      <div className="bg-gradient-to-r from-slate-50 to-indigo-50/40 rounded-2xl border border-slate-200 p-6">
        <div className="flex items-start gap-4">
          <div className="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
            <Info className="w-5 h-5" />
          </div>
          <div className="space-y-2">
            <h4 className="text-sm font-bold text-slate-900">Notification Sound Best Practices</h4>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
              <div className="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                <p className="text-xs font-bold text-slate-800">Independent Configuration</p>
                <p className="text-xs text-slate-500 mt-1">
                  Member sounds and Admin sounds can be updated separately without overriding each other.
                </p>
              </div>
              <div className="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                <p className="text-xs font-bold text-slate-800">Supported Formats & Size</p>
                <p className="text-xs text-slate-500 mt-1">
                  Upload MP3, WAV, OGG, AAC, or M4A files up to 10 MB. Short chimes under 3 seconds are recommended.
                </p>
              </div>
              <div className="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                <p className="text-xs font-bold text-slate-800">Instant Activation</p>
                <p className="text-xs text-slate-500 mt-1">
                  Changes take effect immediately on next notification arrival without needing server reboots.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default NotificationSoundPage;

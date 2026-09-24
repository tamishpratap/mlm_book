import { useState } from 'react';
import { Trash2, Play, Pause, AlertTriangle } from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { confirmHelper } from '../../../utils/confirmHelper';

export function CacheMaintenanceTab({
  systemInfo = {},
  onClearCache,
  onToggleMaintenance,
  loading = false,
}) {
  const [secretKey, setSecretKey] = useState('admin-access');
  const isMaintenanceActive = systemInfo.maintenance_mode === 'Enabled';

  const handleClearCacheClick = (type) => {
    onClearCache(type);
  };

  const handleMaintenanceToggleClick = () => {
    if (isMaintenanceActive) {
      onToggleMaintenance('up');
    } else {
      confirmHelper.confirm({
        header: 'Enable Maintenance Mode',
        message: 'Are you sure you want to put the platform in maintenance mode? Member access will be temporarily paused.',
        onAccept: () => onToggleMaintenance('down', secretKey),
      });
    }
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
      {/* Cache Management */}
      <div className="p-5 border border-slate-200 rounded-2xl bg-white space-y-4 shadow-2xs">
        <div>
          <h6 className="text-xs font-bold text-slate-800 uppercase tracking-wider mb-1">
            Artisan Cache Management
          </h6>
          <p className="text-xs text-slate-500">
            Clear application cache, configuration cache, route cache, and compiled views safely.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2.5 sm:gap-3 pt-2">
          <Button
            type="button"
            label="Clear All Cache"
            icon={<Trash2 className="w-3.5 h-3.5 mr-1" />}
            size="small"
            onClick={() => handleClearCacheClick('all')}
            loading={loading}
            className="p-button-primary text-xs"
          />
          <Button
            type="button"
            label="Clear Config"
            size="small"
            onClick={() => handleClearCacheClick('config')}
            disabled={loading}
            className="p-button-outlined p-button-secondary text-xs"
          />
          <Button
            type="button"
            label="Clear Routes"
            size="small"
            onClick={() => handleClearCacheClick('route')}
            disabled={loading}
            className="p-button-outlined p-button-secondary text-xs"
          />
          <Button
            type="button"
            label="Clear Views"
            size="small"
            onClick={() => handleClearCacheClick('view')}
            disabled={loading}
            className="p-button-outlined p-button-secondary text-xs"
          />
        </div>
      </div>

      {/* Maintenance Mode */}
      <div className="p-5 border border-slate-200 rounded-2xl bg-slate-50 space-y-4">
        <div>
          <h6 className="text-xs font-bold text-slate-800 uppercase tracking-wider mb-1">
            Platform Maintenance Mode
          </h6>
          <div className="flex flex-wrap items-center gap-2 mt-2">
            <span className="text-xs text-slate-500 font-medium">Current Status:</span>
            {isMaintenanceActive ? (
              <span className="inline-flex items-center text-xs font-bold text-red-700 bg-red-50 px-2.5 py-0.5 rounded-full border border-red-200">
                <AlertTriangle className="w-3 h-3 mr-1 text-red-600" />
                Maintenance Mode ACTIVE
              </span>
            ) : (
              <span className="inline-flex items-center text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                Platform LIVE
              </span>
            )}
          </div>
        </div>

        {!isMaintenanceActive && (
          <div>
            <label className="text-[11px] font-bold text-slate-600 block mb-1">
              Bypass Secret Key (e.g. your-secret)
            </label>
            <InputText
              value={secretKey}
              onChange={(e) => setSecretKey(e.target.value)}
              placeholder="admin-access"
              className="w-full text-xs"
              disabled={loading}
            />
          </div>
        )}

        <div className="pt-2">
          {isMaintenanceActive ? (
            <Button
              type="button"
              label="Disable Maintenance Mode"
              icon={<Play className="w-3.5 h-3.5 mr-1" />}
              size="small"
              onClick={handleMaintenanceToggleClick}
              loading={loading}
              className="p-button-success text-xs w-full"
            />
          ) : (
            <Button
              type="button"
              label="Enable Maintenance Mode"
              icon={<Pause className="w-3.5 h-3.5 mr-1" />}
              size="small"
              onClick={handleMaintenanceToggleClick}
              loading={loading}
              className="p-button-warning text-xs w-full"
            />
          )}
        </div>
      </div>
    </div>
  );
}

export default CacheMaintenanceTab;

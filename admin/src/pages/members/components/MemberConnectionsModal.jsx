import { Link } from 'react-router-dom';
import { Dialog } from 'primereact/dialog';
import { Users, UserX, User, MapPin, Calendar } from 'lucide-react';
import { Button } from 'primereact/button';

export function MemberConnectionsModal({
  visible,
  onHide,
  connections = [],
  memberName = 'Member',
}) {
  return (
    <Dialog
      visible={visible}
      onHide={onHide}
      header={
        <div className="flex items-center space-x-2">
          <Users className="w-4 h-4 text-blue-600" />
          <span className="font-bold text-sm text-slate-900">
            {memberName}&apos;s Connections ({connections.length})
          </span>
        </div>
      }
      className="w-full max-w-xl"
      modal
      footer={
        <Button
          label="Close"
          size="small"
          onClick={onHide}
          className="p-button-outlined p-button-secondary text-xs"
        />
      }
    >
      <div className="p-1">
        {connections.length === 0 ? (
          <div className="py-8 text-center text-slate-400">
            <UserX className="w-10 h-10 mx-auto mb-2 text-slate-300 opacity-60" />
            <h6 className="font-bold text-xs text-slate-700 mb-1">No Connections Found</h6>
            <p className="text-[11px] text-slate-400">
              This member currently has no active accepted network connections.
            </p>
          </div>
        ) : (
          <div className="divide-y divide-slate-100 max-h-96 overflow-y-auto pr-1 space-y-1">
            {connections.map((conn, idx) => {
              const name = conn.name || 'Platform Member';
              const initial = name.charAt(0).toUpperCase();
              const locationStr = [conn.city, conn.country].filter(Boolean).join(', ');

              return (
                <div
                  key={conn.id || idx}
                  className="p-3 flex items-center justify-between hover:bg-slate-50 rounded-xl transition-colors bg-white border border-slate-100 mb-1"
                >
                  <div className="flex items-center space-x-3">
                    {conn.avatar_url || conn.profile_photo ? (
                      <img
                        src={conn.avatar_url || conn.profile_photo}
                        alt={name}
                        className="w-10 h-10 rounded-full object-cover ring-2 ring-slate-100 shrink-0"
                        onError={(e) => {
                          e.target.style.display = 'none';
                        }}
                      />
                    ) : (
                      <div className="w-10 h-10 rounded-full bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center shrink-0">
                        {initial}
                      </div>
                    )}
                    <div>
                      <h5 className="text-xs font-bold text-slate-900 mb-0">{name}</h5>
                      <div className="flex items-center space-x-1 text-[11px] text-slate-500">
                        <code className="text-blue-600 font-mono">{conn.user_id}</code>
                        {locationStr && (
                          <>
                            <span>•</span>
                            <span className="flex items-center">
                              <MapPin className="w-2.5 h-2.5 mr-0.5 text-slate-400" />
                              {locationStr}
                            </span>
                          </>
                        )}
                      </div>
                      {conn.connected_at && (
                        <span className="text-[10px] text-slate-400 flex items-center mt-0.5">
                          <Calendar className="w-2.5 h-2.5 mr-1 text-emerald-500" />
                          Connected on {conn.connected_at_human || conn.connected_at}
                        </span>
                      )}
                    </div>
                  </div>

                  <div>
                    <Link
                      to={`/admin/members/${conn.id}`}
                      onClick={onHide}
                      className="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors border border-blue-100"
                    >
                      <User className="w-3 h-3 mr-1" />
                      View Profile
                    </Link>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </Dialog>
  );
}

export default MemberConnectionsModal;

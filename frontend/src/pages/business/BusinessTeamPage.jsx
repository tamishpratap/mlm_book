import { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  UsersRound,
  UserPlus,
  ArrowLeft,
  Users,
  ShieldCheck,
  Edit3,
  Mail,
  Crown,
  Edit,
  UserX,
  X,
  Send,
  Shield,
  RotateCw,
} from 'lucide-react';
import businessApi from '../../api/businessApi';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import MemberAvatar from '../../components/common/MemberAvatar';
import { ModalPortal } from '../../components/common/ModalPortal';

function formatDate(dateString) {
  if (!dateString) return '';
  const d = new Date(dateString);
  return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

function getInitials(name) {
  if (!name) return 'TM';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'TM';
}

export function BusinessTeamPage() {
  const { slug } = useParams();

  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  // Invite modal state
  const [isInviteModalOpen, setIsInviteModalOpen] = useState(false);
  const [inviteeId, setInviteeId] = useState('');
  const [inviteRole, setInviteRole] = useState('editor');
  const [isInviting, setIsInviting] = useState(false);
  const [inviteError, setInviteError] = useState(null);

  // Change role modal state
  const [isChangeRoleModalOpen, setIsChangeRoleModalOpen] = useState(false);
  const [selectedMember, setSelectedMember] = useState(null);
  const [newRole, setNewRole] = useState('editor');
  const [isChangingRole, setIsChangingRole] = useState(false);
  const [roleError, setRoleError] = useState(null);

  const fetchTeam = useCallback(() => {
    if (!slug) return Promise.resolve(null);
    return businessApi.getTeam(slug);
  }, [slug]);

  useEffect(() => {
    let isMounted = true;

    fetchTeam()
      .then((res) => {
        if (isMounted && res) {
          setData(res);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load team data or unauthorized.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchTeam]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchTeam()
      .then((res) => {
        if (res) setData(res);
      })
      .catch(() => {})
      .finally(() => setIsRefreshing(false));
  };

  const handleOpenInvite = () => {
    setInviteeId('');
    setInviteRole('editor');
    setInviteError(null);
    setIsInviteModalOpen(true);
  };

  const handleCloseInvite = () => {
    setIsInviteModalOpen(false);
  };

  const handleSendInvite = async (e) => {
    e.preventDefault();
    if (!inviteeId || isInviting) return;

    setIsInviting(true);
    setInviteError(null);

    try {
      await businessApi.inviteTeamMember(slug, {
        invitee_id: parseInt(inviteeId, 10),
        role: inviteRole,
      });
      setIsInviteModalOpen(false);
      handleRefresh();
    } catch (err) {
      setInviteError(err.response?.data?.message || 'Failed to send team invitation.');
    } finally {
      setIsInviting(false);
    }
  };

  const handleCancelInvite = async (invId) => {
    if (!window.confirm('Are you sure you want to cancel this invitation?')) return;
    try {
      await businessApi.cancelTeamInvitation(slug, invId);
      handleRefresh();
    } catch {
      alert('Failed to cancel invitation.');
    }
  };

  const handleOpenChangeRole = (tm) => {
    setSelectedMember(tm);
    setNewRole(tm.role || 'editor');
    setRoleError(null);
    setIsChangeRoleModalOpen(true);
  };

  const handleCloseChangeRole = () => {
    setIsChangeRoleModalOpen(false);
    setSelectedMember(null);
  };

  const handleSaveRole = async (e) => {
    e.preventDefault();
    if (!selectedMember || isChangingRole) return;

    setIsChangingRole(true);
    setRoleError(null);

    try {
      await businessApi.updateTeamMemberRole(slug, selectedMember.id, newRole);
      setIsChangeRoleModalOpen(false);
      handleRefresh();
    } catch (err) {
      setRoleError(err.response?.data?.message || 'Failed to update role.');
    } finally {
      setIsChangingRole(false);
    }
  };

  const handleRemoveMember = async (tmId, name) => {
    if (!window.confirm(`Are you sure you want to remove ${name} from the business team?`)) return;
    try {
      await businessApi.removeTeamMember(slug, tmId);
      handleRefresh();
    } catch {
      alert('Failed to remove team member.');
    }
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
        Loading team management...
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="card" style={{ maxWidth: '800px', margin: '40px auto', padding: '32px', textAlign: 'center' }}>
        <h2 style={{ fontSize: '18px', color: '#dc2626', marginBottom: '8px' }}>Unauthorized or Not Found</h2>
        <p style={{ color: 'var(--color-text-secondary)', marginBottom: '20px' }}>
          {error || 'You do not have administrative access to manage this business team.'}
        </p>
        <Link to={`/member/business-pages/${slug}`} className="member-button member-button--primary">
          Back to Profile
        </Link>
      </div>
    );
  }

  const businessPage = data.business_page || {};
  const activeMembers = data.active_members || [];
  const pendingInvitations = data.pending_invitations || [];
  const owner = data.owner || {};
  const friends = data.friends || [];
  const roles = data.roles || {
    owner: 'Page Owner',
    admin: 'Page Admin',
    editor: 'Page Editor',
    moderator: 'Page Moderator',
    analyst: 'Page Analyst',
  };
  const isOwner = Boolean(data.is_owner);

  const totalMembersCount = activeMembers.length + 1;
  const adminCount = activeMembers.filter((m) => m.role === 'admin').length + 1;
  const editorCount = activeMembers.filter((m) => m.role === 'editor').length;
  const pendingCount = pendingInvitations.length;

  return (
    <div className="biz-page" style={{ padding: '24px 0', width: '100%', margin: '0 auto' }}>
      {/* Header */}
      <header className="biz-header" style={{ marginBottom: '24px' }}>
        <div className="biz-header__info">
          <h1>
            <UsersRound size={24} aria-hidden="true" />
            <span>Team Management — {businessPage.page_name}</span>
          </h1>
          <p>Assign enterprise roles, manage team permissions, and invite team members.</p>
        </div>
        <div className="biz-header__actions" style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
          <button
            type="button"
            className="member-button member-button--secondary"
            onClick={handleRefresh}
            disabled={isRefreshing}
            style={{ padding: '8px 12px' }}
            aria-label="Refresh Team"
          >
            <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} />
          </button>
          <button type="button" className="member-button member-button--primary" onClick={handleOpenInvite}>
            <UserPlus size={16} aria-hidden="true" />
            <span>Invite Team Member</span>
          </button>
          <Link to={`/member/business-pages/${slug}`} className="member-button member-button--secondary">
            <ArrowLeft size={15} aria-hidden="true" />
            <span>Back to Profile</span>
          </Link>
        </div>
      </header>

      {/* Metric Counters Summary Cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '16px', marginBottom: '24px' }}>
        <div className="biz-info-card" style={{ padding: '16px', display: 'flex', alignItems: 'center', flexDirection: 'row', gap: '14px' }}>
          <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: 'rgba(79, 125, 243, 0.1)', color: '#4f7df3', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <Users size={22} />
          </div>
          <div>
            <strong style={{ fontSize: '20px', color: '#1d2738', display: 'block' }}>{totalMembersCount}</strong>
            <span style={{ fontSize: '12.5px', color: '#687386' }}>Total Team Members</span>
          </div>
        </div>

        <div className="biz-info-card" style={{ padding: '16px', display: 'flex', alignItems: 'center', flexDirection: 'row', gap: '14px' }}>
          <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: 'rgba(32, 200, 117, 0.1)', color: '#20c875', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <ShieldCheck size={22} />
          </div>
          <div>
            <strong style={{ fontSize: '20px', color: '#1d2738', display: 'block' }}>{adminCount}</strong>
            <span style={{ fontSize: '12.5px', color: '#687386' }}>Admins & Owners</span>
          </div>
        </div>

        <div className="biz-info-card" style={{ padding: '16px', display: 'flex', alignItems: 'center', flexDirection: 'row', gap: '14px' }}>
          <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: 'rgba(138, 43, 226, 0.1)', color: '#8a2be2', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <Edit3 size={22} />
          </div>
          <div>
            <strong style={{ fontSize: '20px', color: '#1d2738', display: 'block' }}>{editorCount}</strong>
            <span style={{ fontSize: '12.5px', color: '#687386' }}>Editors</span>
          </div>
        </div>

        <div className="biz-info-card" style={{ padding: '16px', display: 'flex', alignItems: 'center', flexDirection: 'row', gap: '14px' }}>
          <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: 'rgba(247, 185, 64, 0.15)', color: '#b7791f', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <Mail size={22} />
          </div>
          <div>
            <strong style={{ fontSize: '20px', color: '#1d2738', display: 'block' }}>{pendingCount}</strong>
            <span style={{ fontSize: '12.5px', color: '#687386' }}>Pending Invites</span>
          </div>
        </div>
      </div>

      {/* Active Team Members Card */}
      <div className="biz-info-card" style={{ marginBottom: '24px', background: '#fff', borderRadius: '18px', padding: '24px', border: '1px solid #e7ecf4' }}>
        <h3 style={{ fontSize: '17px', fontWeight: 700, color: '#1d2738', margin: '0 0 16px 0', display: 'flex', alignItems: 'center', gap: '8px' }}>
          <Users size={18} color="#4f7df3" />
          <span>Active Team Members</span>
        </h3>

        <div style={{ overflowX: 'auto' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
            <thead>
              <tr style={{ borderBottom: '1px solid #e7ecf4', fontSize: '12px', color: '#98a2b3', textTransform: 'uppercase' }}>
                <th style={{ padding: '12px 8px' }}>Member</th>
                <th style={{ padding: '12px 8px' }}>Role</th>
                <th style={{ padding: '12px 8px' }}>Joined Date</th>
                <th style={{ padding: '12px 8px', textAlign: 'right' }}>Actions</th>
              </tr>
            </thead>
            <tbody>
              {/* Owner Row */}
              <tr style={{ borderBottom: '1px solid #f4f6fa' }}>
                <td style={{ padding: '14px 8px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <MemberAvatar member={owner} size={40} />
                    <div>
                      <strong style={{ fontSize: '14px', color: '#1d2738', display: 'inline-flex', alignItems: 'center' }}>
                        <span>{owner.name}</span>
                        <VerifiedBadge member={owner} size={14} />
                      </strong>
                      <span style={{ fontSize: '12px', color: '#98a2b3', display: 'block' }}>@{owner.user_id || 'owner'}</span>
                    </div>
                  </div>
                </td>
                <td style={{ padding: '14px 8px' }}>
                  <span className="biz-badge biz-badge--category" style={{ background: 'rgba(138, 43, 226, 0.1)', color: '#8a2be2', fontWeight: 700, display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                    <Crown size={12} />
                    <span>Page Owner</span>
                  </span>
                </td>
                <td style={{ padding: '14px 8px', fontSize: '13px', color: '#687386' }}>
                  {formatDate(businessPage.created_at)}
                </td>
                <td style={{ padding: '14px 8px', textAlign: 'right' }}>
                  <span style={{ fontSize: '12px', color: '#98a2b3', fontStyle: 'italic' }}>Primary Owner</span>
                </td>
              </tr>

              {/* Active Team Members */}
              {activeMembers.map((tm) => {
                const m = tm.member || {};
                const canModifyThisMember = isOwner || tm.role !== 'admin';
                return (
                  <tr key={tm.id} style={{ borderBottom: '1px solid #f4f6fa' }}>
                    <td style={{ padding: '14px 8px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                        <MemberAvatar member={m} size={40} />
                        <div>
                          <strong style={{ fontSize: '14px', color: '#1d2738', display: 'inline-flex', alignItems: 'center' }}>
                            <span>{m.name}</span>
                            <VerifiedBadge member={m} size={14} />
                          </strong>
                          <span style={{ fontSize: '12px', color: '#98a2b3', display: 'block' }}>@{m.user_id || 'member'}</span>
                        </div>
                      </div>
                    </td>
                    <td style={{ padding: '14px 8px' }}>
                      <span className="biz-badge biz-badge--category">
                        {roles[tm.role] || tm.role}
                      </span>
                    </td>
                    <td style={{ padding: '14px 8px', fontSize: '13px', color: '#687386' }}>
                      {formatDate(tm.joined_at || tm.created_at)}
                    </td>
                    <td style={{ padding: '14px 8px', textAlign: 'right' }}>
                      {canModifyThisMember ? (
                        <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
                          <button
                            type="button"
                            className="member-button member-button--secondary"
                            style={{ padding: '4px 10px', fontSize: '12px' }}
                            onClick={() => handleOpenChangeRole(tm)}
                          >
                            <Edit size={12} aria-hidden="true" />
                            <span>Role</span>
                          </button>
                          <button
                            type="button"
                            className="member-button member-button--danger"
                            style={{ padding: '4px 10px', fontSize: '12px' }}
                            onClick={() => handleRemoveMember(tm.id, m.name)}
                          >
                            <UserX size={12} aria-hidden="true" />
                            <span>Remove</span>
                          </button>
                        </div>
                      ) : (
                        <span style={{ fontSize: '12px', color: '#98a2b3', fontStyle: 'italic' }}>Protected</span>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      {/* Pending Invitations Card */}
      <div className="biz-info-card" style={{ background: '#fff', borderRadius: '18px', padding: '24px', border: '1px solid #e7ecf4' }}>
        <h3 style={{ fontSize: '17px', fontWeight: 700, color: '#1d2738', margin: '0 0 16px 0', display: 'flex', alignItems: 'center', gap: '8px' }}>
          <Mail size={18} color="#f7b940" />
          <span>Pending Invitations ({pendingInvitations.length})</span>
        </h3>

        <div style={{ overflowX: 'auto' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
            <thead>
              <tr style={{ borderBottom: '1px solid #e7ecf4', fontSize: '12px', color: '#98a2b3', textTransform: 'uppercase' }}>
                <th style={{ padding: '12px 8px' }}>Invited Member</th>
                <th style={{ padding: '12px 8px' }}>Assigned Role</th>
                <th style={{ padding: '12px 8px' }}>Invited By</th>
                <th style={{ padding: '12px 8px' }}>Sent Date</th>
                <th style={{ padding: '12px 8px', textAlign: 'right' }}>Action</th>
              </tr>
            </thead>
            <tbody>
              {pendingInvitations.length === 0 ? (
                <tr>
                  <td colSpan="5" style={{ textAlign: 'center', padding: '24px', color: '#98a2b3', fontStyle: 'italic' }}>
                    No pending team invitations.
                  </td>
                </tr>
              ) : (
                pendingInvitations.map((inv) => (
                  <tr key={inv.id} style={{ borderBottom: '1px solid #f4f6fa' }}>
                    <td style={{ padding: '12px 8px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <MemberAvatar member={inv.invitee} size={34} />
                        <strong style={{ fontSize: '13.5px', color: '#1d2738' }}>{inv.invitee?.name || 'Member'}</strong>
                      </div>
                    </td>
                    <td style={{ padding: '12px 8px' }}>
                      <span className="biz-badge biz-badge--visibility">
                        {roles[inv.role] || inv.role}
                      </span>
                    </td>
                    <td style={{ padding: '12px 8px', fontSize: '13px', color: '#687386' }}>
                      {inv.inviter?.name || 'Admin'}
                    </td>
                    <td style={{ padding: '12px 8px', fontSize: '13px', color: '#687386' }}>
                      {formatDate(inv.created_at)}
                    </td>
                    <td style={{ padding: '12px 8px', textAlign: 'right' }}>
                      <button
                        type="button"
                        className="member-button member-button--danger"
                        style={{ padding: '4px 10px', fontSize: '12px' }}
                        onClick={() => handleCancelInvite(inv.id)}
                      >
                        Cancel
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Invite Modal */}
      {isInviteModalOpen && (
        <ModalPortal isOpen={isInviteModalOpen} onClose={handleCloseInvite}>
          <div
            className="biz-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="invite-team-modal-title"
            style={{
              background: '#fff',
              borderRadius: '20px',
              maxWidth: '480px',
              width: '100%',
              boxShadow: '0 20px 40px rgba(0,0,0,0.2)',
              overflow: 'hidden',
            }}
          >
            <div
              style={{
                padding: '18px 24px',
                borderBottom: '1px solid #e7ecf4',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
              }}
            >
              <h3 id="invite-team-modal-title" style={{ fontSize: '17px', fontWeight: 700, margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                <UserPlus size={18} color="#4f7df3" />
                <span>Invite Team Member</span>
              </h3>
              <button
                type="button"
                className="icon-button"
                onClick={handleCloseInvite}
                style={{ background: 'transparent', border: 'none', cursor: 'pointer' }}
              >
                <X size={18} />
              </button>
            </div>

            <form onSubmit={handleSendInvite} style={{ padding: '24px', display: 'flex', flexDirection: 'column', gap: '16px' }}>
              {inviteError && (
                <div style={{ padding: '10px 14px', background: '#fee2e2', color: '#b91c1c', borderRadius: '10px', fontSize: '13px' }}>
                  {inviteError}
                </div>
              )}

              <div className="form-group">
                <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  Select Connection / Friend
                </label>
                {friends.length > 0 ? (
                  <select
                    value={inviteeId}
                    onChange={(e) => setInviteeId(e.target.value)}
                    className="biz-filter-select"
                    style={{ width: '100%' }}
                    required
                  >
                    <option value="">Choose a friend to invite...</option>
                    {friends.map((fr) => (
                      <option key={fr.id} value={fr.id}>
                        {fr.name} (@{fr.user_id || 'user'})
                      </option>
                    ))}
                  </select>
                ) : (
                  <input
                    type="number"
                    value={inviteeId}
                    onChange={(e) => setInviteeId(e.target.value)}
                    placeholder="Enter Member ID..."
                    className="biz-search-input"
                    required
                  />
                )}
              </div>

              <div className="form-group">
                <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  Assign Role
                </label>
                <select
                  value={inviteRole}
                  onChange={(e) => setInviteRole(e.target.value)}
                  className="biz-filter-select"
                  style={{ width: '100%' }}
                  required
                >
                  {isOwner && (
                    <option value="admin">Page Admin (Full management access)</option>
                  )}
                  <option value="editor">Page Editor (Can publish posts & media)</option>
                  <option value="moderator">Page Moderator (Can manage comments)</option>
                  <option value="analyst">Page Analyst (Read-only insights access)</option>
                </select>
              </div>

              <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end', marginTop: '12px' }}>
                <button type="button" className="member-button member-button--secondary" onClick={handleCloseInvite}>
                  Cancel
                </button>
                <button
                  type="submit"
                  className="member-button member-button--primary"
                  disabled={isInviting || !inviteeId}
                >
                  <Send size={14} />
                  <span>{isInviting ? 'Sending...' : 'Send Invitation'}</span>
                </button>
              </div>
            </form>
          </div>
        </ModalPortal>
      )}

      {/* Change Role Modal */}
      {isChangeRoleModalOpen && selectedMember && (
        <ModalPortal isOpen={isChangeRoleModalOpen} onClose={handleCloseChangeRole}>
          <div
            className="biz-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="change-role-modal-title"
            style={{
              background: '#fff',
              borderRadius: '20px',
              maxWidth: '440px',
              width: '100%',
              boxShadow: '0 20px 40px rgba(0,0,0,0.2)',
              overflow: 'hidden',
            }}
          >
            <div
              style={{
                padding: '18px 24px',
                borderBottom: '1px solid #e7ecf4',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
              }}
            >
              <h3 id="change-role-modal-title" style={{ fontSize: '17px', fontWeight: 700, margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                <Shield size={18} color="#4f7df3" />
                <span>Change Team Role</span>
              </h3>
              <button
                type="button"
                className="icon-button"
                onClick={handleCloseChangeRole}
                style={{ background: 'transparent', border: 'none', cursor: 'pointer' }}
              >
                <X size={18} />
              </button>
            </div>

            <form onSubmit={handleSaveRole} style={{ padding: '24px', display: 'flex', flexDirection: 'column', gap: '16px' }}>
              {roleError && (
                <div style={{ padding: '10px 14px', background: '#fee2e2', color: '#b91c1c', borderRadius: '10px', fontSize: '13px' }}>
                  {roleError}
                </div>
              )}

              <p style={{ margin: 0, fontSize: '13.5px', color: '#687386' }}>
                Changing role for <strong>{selectedMember.member?.name}</strong>:
              </p>

              <div className="form-group">
                <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  New Role
                </label>
                <select
                  value={newRole}
                  onChange={(e) => setNewRole(e.target.value)}
                  className="biz-filter-select"
                  style={{ width: '100%' }}
                  required
                >
                  {isOwner && <option value="admin">Page Admin</option>}
                  <option value="editor">Page Editor</option>
                  <option value="moderator">Page Moderator</option>
                  <option value="analyst">Page Analyst</option>
                </select>
              </div>

              <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end', marginTop: '12px' }}>
                <button type="button" className="member-button member-button--secondary" onClick={handleCloseChangeRole}>
                  Cancel
                </button>
                <button
                  type="submit"
                  className="member-button member-button--primary"
                  disabled={isChangingRole}
                >
                  <span>{isChangingRole ? 'Saving...' : 'Save Role'}</span>
                </button>
              </div>
            </form>
          </div>
        </ModalPortal>
      )}
    </div>
  );
}

export default BusinessTeamPage;

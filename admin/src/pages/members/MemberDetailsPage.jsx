import { useState, useEffect, useCallback } from 'react';
import { useParams } from 'react-router-dom';
import { TabView, TabPanel } from 'primereact/tabview';
import { PageHeader } from '../../components/common/PageHeader';
import { ErrorState } from '../../components/common/ErrorState';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { membersApi } from '../../api';

// Subcomponents
import { MemberDetailsSkeleton } from './components/MemberDetailsSkeleton';
import { MemberProfileBanner } from './components/MemberProfileBanner';
import { MemberWhatsAppVerificationCard } from './components/MemberWhatsAppVerificationCard';
import { MemberConnectionsModal } from './components/MemberConnectionsModal';
import { MemberOverviewTab } from './components/tabs/MemberOverviewTab';
import { MemberPostsTab } from './components/tabs/MemberPostsTab';
import { MemberStoriesTab } from './components/tabs/MemberStoriesTab';
import { MemberCommunitiesTab } from './components/tabs/MemberCommunitiesTab';
import { MemberMarketplaceTab } from './components/tabs/MemberMarketplaceTab';
// import { MemberEventsTab } from './components/tabs/MemberEventsTab';
import { MemberReportsTab } from './components/tabs/MemberReportsTab';

export function MemberDetailsPage() {
  const { id } = useParams();
  const { showSuccess, showError, showInfo } = useToast();

  const [member, setMember] = useState(null);
  const [posts, setPosts] = useState([]);
  const [stories, setStories] = useState([]);
  const [communities, setCommunities] = useState([]);
  const [products, setProducts] = useState([]);
  const [events, setEvents] = useState([]);
  const [reports, setReports] = useState([]);
  const [connections, setConnections] = useState([]);
  const [connectionsCount, setConnectionsCount] = useState(0);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);
  const [showConnectionsModal, setShowConnectionsModal] = useState(false);
  const [activeTabIndex, setActiveTabIndex] = useState(0);

  const loadMemberDetails = useCallback(async (isRefresh = false) => {
    if (!isRefresh) {
      setLoading(true);
    }
    setError(null);
    try {
      const res = await membersApi.getMember(id);
      const memberData = res?.member || res?.data || res;
      setMember(memberData);
      setPosts(res?.posts || memberData?.posts || []);
      setStories(res?.stories || memberData?.stories || []);
      setCommunities(res?.communities || memberData?.joinedCommunities || []);
      setProducts(res?.products || memberData?.products || []);
      setEvents(res?.events || memberData?.events || []);
      setReports(res?.reports || memberData?.reports || []);
      setConnections(res?.connections || []);
      setConnectionsCount(res?.connectionsCount || res?.connections?.length || 0);
    } catch (err) {
      setError(err.message || 'Failed to load member profile details.');
    } finally {
      if (!isRefresh) {
        setLoading(false);
      }
    }
  }, [id]);

  useEffect(() => {
    let isMounted = true;
    loadMemberDetails();
    return () => {
      isMounted = false;
    };
  }, [loadMemberDetails]);

  const handleRefresh = useCallback(() => {
    return loadMemberDetails(true);
  }, [loadMemberDetails]);

  // Actions
  const handleToggleBlock = () => {
    if (!member || actionLoading) return;
    const isBlocked = Boolean(member.blocked_at || member.is_blocked || member.status === 'blocked');

    if (isBlocked) {
      confirmHelper.confirmUnblock({
        userName: `${member.name} (${member.user_id})`,
        onAccept: async () => {
          if (actionLoading) return;
          setActionLoading(true);
          try {
            const res = await membersApi.unblockMember(member.id);
            showSuccess(res?.message || `Member ${member.name} has been unblocked.`);
            if (res?.member) {
              setMember((prev) => ({ ...prev, ...res.member }));
            }
            await handleRefresh();
          } catch (err) {
            showError(err.message || 'Failed to unblock member.');
          } finally {
            setActionLoading(false);
          }
        },
      });
    } else {
      confirmHelper.confirmBlock({
        userName: `${member.name} (${member.user_id})`,
        onAccept: async () => {
          if (actionLoading) return;
          setActionLoading(true);
          try {
            const res = await membersApi.blockMember(member.id);
            showSuccess(res?.message || `Member ${member.name} has been blocked.`);
            if (res?.member) {
              setMember((prev) => ({ ...prev, ...res.member }));
            }
            await handleRefresh();
          } catch (err) {
            showError(err.message || 'Failed to block member.');
          } finally {
            setActionLoading(false);
          }
        },
      });
    }
  };

  const handleToggleStatus = async (action) => {
    if (!member || actionLoading) return;
    setActionLoading(true);
    try {
      const res = await membersApi.updateStatus(member.id, action);
      showSuccess(res?.message || 'Member status updated.');
      if (res?.member) {
        setMember((prev) => ({ ...prev, ...res.member }));
      }
      await handleRefresh();
    } catch (err) {
      showError(err.message || 'Failed to update member status.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleApproveVerification = () => {
    if (!member || actionLoading) return;
    confirmHelper.confirm({
      header: 'Approve WhatsApp Verification',
      message: `Approve WhatsApp verification for ${member.name} (${member.user_id})? Please ensure you have verified that their incoming 'Hi' message on WhatsApp was received from ${member.phone}.`,
      icon: 'pi pi-check-circle text-emerald-500',
      acceptLabel: 'Approve Verification',
      rejectLabel: 'Cancel',
      acceptClassName: 'p-button-success text-xs',
      onAccept: async () => {
        if (actionLoading) return;
        setActionLoading(true);
        try {
          const res = await membersApi.approveMember(member.id);
          showSuccess(res?.message || 'WhatsApp verification approved successfully.');
          if (res?.member) {
            setMember((prev) => ({ ...prev, ...res.member }));
          }
          await handleRefresh();
        } catch (err) {
          showError(err.message || 'Failed to approve WhatsApp verification.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleRejectVerification = () => {
    if (!member || actionLoading) return;
    confirmHelper.confirm({
      header: 'Dismiss Verification Request',
      message: `Dismiss WhatsApp verification request for ${member.name} (${member.user_id})? The member will remain unverified.`,
      icon: 'pi pi-exclamation-triangle text-amber-500',
      acceptLabel: 'Dismiss Request',
      rejectLabel: 'Cancel',
      acceptClassName: 'p-button-warning text-xs',
      onAccept: async () => {
        if (actionLoading) return;
        setActionLoading(true);
        try {
          const res = await membersApi.rejectMember(member.id);
          showInfo(res?.message || 'Verification request dismissed.');
          await handleRefresh();
        } catch (err) {
          showError(err.message || 'Failed to dismiss verification request.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  if (loading) {
    return <MemberDetailsSkeleton />;
  }

  if (error || !member) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Member Profile"
          subtitle="Read-only profile details, content moderation & community statistics."
          breadcrumbs={[{ label: 'Members', to: '/admin/members' }, { label: 'Profile Inspector' }]}
        />
        <ErrorState
          title="Member Record Not Found"
          message={error || 'The requested member could not be located in the database.'}
          onRetry={loadMemberDetails}
        />
      </div>
    );
  }

  const displayName = member.name || 'Anonymous Member';

  return (
    <div className="space-y-6">
      {/* Network Connections Modal */}
      <MemberConnectionsModal
        visible={showConnectionsModal}
        onHide={() => setShowConnectionsModal(false)}
        connections={connections}
        memberName={displayName}
      />

      {/* Page Header */}
      <PageHeader
        title={`Member Profile: ${displayName}`}
        subtitle="Read-only profile details, content moderation & community statistics."
        breadcrumbs={[
          { label: 'Members', to: '/admin/members' },
          { label: displayName },
        ]}
      />

      {/* Banner & Metric Header */}
      <MemberProfileBanner
        member={member}
        counts={{
          posts: posts.length,
          stories: stories.length,
          communities: communities.length,
          products: products.length,
          events: events.length,
          reports: reports.length,
          connections: connectionsCount,
        }}
        onToggleBlock={handleToggleBlock}
        onToggleStatus={handleToggleStatus}
        onOpenConnections={() => setShowConnectionsModal(true)}
        actionLoading={actionLoading}
      />

      {/* WhatsApp Verification Card */}
      <MemberWhatsAppVerificationCard
        member={member}
        onApprove={handleApproveVerification}
        onReject={handleRejectVerification}
        loading={actionLoading}
      />

      {/* 7-Tab Details Container */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6">
        <TabView
          activeIndex={activeTabIndex}
          onTabChange={(e) => setActiveTabIndex(e.index)}
        >
          {/* Tab 1: Overview & Info */}
          <TabPanel header="Overview & Info">
            <MemberOverviewTab member={member} />
          </TabPanel>

          {/* Tab 2: Posts */}
          <TabPanel header={`Posts (${posts.length})`}>
            <MemberPostsTab
              posts={posts}
              member={member}
              onRefresh={handleRefresh}
            />
          </TabPanel>

          {/* Tab 3: Stories */}
          <TabPanel header={`Stories (${stories.length})`}>
            <MemberStoriesTab
              stories={stories}
              onRefresh={handleRefresh}
            />
          </TabPanel>

          {/* Tab 4: Communities */}
          <TabPanel header={`Communities (${communities.length})`}>
            <MemberCommunitiesTab
              communities={communities}
              member={member}
              onRefresh={handleRefresh}
            />
          </TabPanel>

          {/* Tab 5: Marketplace */}
          <TabPanel header={`Marketplace (${products.length})`}>
            <MemberMarketplaceTab
              products={products}
              onRefresh={handleRefresh}
            />
          </TabPanel>

          {/* Tab 6: Events - Temporarily Disabled */}
          {/*
          <TabPanel header={`Events (${events.length})`}>
            <MemberEventsTab
              events={events}
              onRefresh={handleRefresh}
            />
          </TabPanel>
          */}

          {/* Tab 7: Reports */}
          <TabPanel header={`Reports (${reports.length})`}>
            <MemberReportsTab
              reports={reports}
              onRefresh={handleRefresh}
            />
          </TabPanel>
        </TabView>
      </div>
    </div>
  );
}

export default MemberDetailsPage;

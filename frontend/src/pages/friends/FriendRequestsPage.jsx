import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { UsersRound, RotateCw } from 'lucide-react';
import friendApi from '../../api/friendApi';
import ConnectionRequestRow from '../../components/friends/ConnectionRequestRow';
import FeedRightSidebar from '../../components/posts/FeedRightSidebar';

export function FriendRequestsPage() {
  const [incomingRequests, setIncomingRequests] = useState([]);
  const [outgoingRequests, setOutgoingRequests] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState('all'); // 'all' | 'incoming' | 'sent'

  useEffect(() => {
    let isMounted = true;
    friendApi
      .getFriendRequests()
      .then((data) => {
        if (isMounted) {
          if (data) {
            setIncomingRequests(data.incoming_requests || []);
            setOutgoingRequests(data.outgoing_requests || []);
          } else {
            setIncomingRequests([]);
            setOutgoingRequests([]);
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load connection requests.');
        }
      })
      .finally(() => {
        if (isMounted) {
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleRefresh = useCallback(() => {
    setIsRefreshing(true);
    friendApi
      .getFriendRequests()
      .then((data) => {
        if (data) {
          setIncomingRequests(data.incoming_requests || []);
          setOutgoingRequests(data.outgoing_requests || []);
        }
      })
      .catch(() => {})
      .finally(() => {
        setIsRefreshing(false);
      });
  }, []);

  const handleIncomingStateChange = (reqId, newState) => {
    // If accepted or rejected, remove from incoming list
    if (newState === 'friends' || newState === 'none') {
      setIncomingRequests((prev) => prev.filter((r) => r.id !== reqId && r.friendship_id !== reqId));
    }
  };

  const handleOutgoingStateChange = (reqId, newState) => {
    // If cancelled, remove from outgoing list
    if (newState === 'none') {
      setOutgoingRequests((prev) => prev.filter((r) => r.id !== reqId && r.friendship_id !== reqId));
    }
  };

  return (
    <>
      <main className="member-main feed" id="friend-requests-page-main">
        <div className="friends-page">
          <header className="member-page-heading friend-page-heading">
            <div>
              <span className="friend-page-eyebrow">Connections</span>
              <h1>Connection Requests</h1>
              <p>Review incoming requests and your pending sent requests.</p>
            </div>
            <div className="friend-page-heading__actions">
              <button
                className="member-button member-button--secondary"
                type="button"
                aria-label="Refresh Requests"
                onClick={handleRefresh}
                disabled={isRefreshing}
                style={{ padding: '8px 12px' }}
              >
                <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
              </button>
              <Link className="member-button member-button--secondary" to="/member/friends">
                <UsersRound size={16} aria-hidden="true" />
                <span>My Connections</span>
              </Link>
            </div>
          </header>

          <div className="friend-requests-tabs" style={{ display: 'flex', gap: '8px', marginBottom: '8px' }}>
            <button
              type="button"
              className={`member-button ${activeTab === 'all' ? 'member-button--primary' : 'member-button--secondary'}`}
              onClick={() => setActiveTab('all')}
              style={{ padding: '6px 14px', fontSize: '13px', borderRadius: '20px' }}
            >
              All ({incomingRequests.length + outgoingRequests.length})
            </button>
            <button
              type="button"
              className={`member-button ${activeTab === 'incoming' ? 'member-button--primary' : 'member-button--secondary'}`}
              onClick={() => setActiveTab('incoming')}
              style={{ padding: '6px 14px', fontSize: '13px', borderRadius: '20px' }}
            >
              Incoming ({incomingRequests.length})
            </button>
            <button
              type="button"
              className={`member-button ${activeTab === 'sent' ? 'member-button--primary' : 'member-button--secondary'}`}
              onClick={() => setActiveTab('sent')}
              style={{ padding: '6px 14px', fontSize: '13px', borderRadius: '20px' }}
            >
              Sent ({outgoingRequests.length})
            </button>
          </div>

          {isLoading ? (
            <div style={{ padding: '32px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
              Loading connection requests...
            </div>
          ) : error ? (
            <section className="member-card friend-empty" role="alert">
              <h2>Error Loading Requests</h2>
              <p>{error}</p>
              <button
                className="member-button member-button--primary"
                type="button"
                onClick={handleRefresh}
              >
                Try Again
              </button>
            </section>
          ) : (
            <>
              {/* SECTION 1: Incoming Requests */}
              {(activeTab === 'all' || activeTab === 'incoming') && (
                <section className="card connection-requests-card friend-request-section">
                  <header className="friend-section-heading" style={{ marginBottom: '16px' }}>
                    <div>
                      <h2 style={{ fontSize: '18px', fontWeight: '700', margin: 0 }}>Incoming Connection Requests</h2>
                      <p style={{ margin: '4px 0 0 0', fontSize: '13px', color: 'var(--color-text-secondary, #64748b)' }}>
                        <span data-incoming-count>{incomingRequests.length}</span> waiting
                      </p>
                    </div>
                  </header>

                  <div className="connection-requests-list" data-incoming-request-list>
                    {incomingRequests.length > 0 ? (
                      incomingRequests.map((req) => (
                        <div key={req.id} data-incoming-request-card>
                          <ConnectionRequestRow
                            request={req}
                            type="incoming"
                            onAction={(reqId, newState) => handleIncomingStateChange(reqId, newState)}
                          />
                        </div>
                      ))
                    ) : (
                      <div
                        className="friend-empty friend-empty--compact"
                        data-incoming-empty
                        style={{ padding: '32px 20px', textAlign: 'center' }}
                      >
                        <p style={{ margin: 0, color: 'var(--color-text-secondary, #64748b)' }}>No new connection requests</p>
                      </div>
                    )}
                  </div>
                </section>
              )}

              {/* SECTION 2: Outgoing / Sent Requests */}
              {(activeTab === 'all' || activeTab === 'sent') && (
                <section className="card connection-requests-card friend-request-section" style={{ marginTop: '24px' }}>
                  <header className="friend-section-heading" style={{ marginBottom: '16px' }}>
                    <div>
                      <h2 style={{ fontSize: '18px', fontWeight: '700', margin: 0 }}>Sent Connection Requests</h2>
                      <p style={{ margin: '4px 0 0 0', fontSize: '13px', color: 'var(--color-text-secondary, #64748b)' }}>
                        <span data-outgoing-count>{outgoingRequests.length}</span> pending
                      </p>
                    </div>
                  </header>

                  <div className="connection-requests-list" data-outgoing-request-list>
                    {outgoingRequests.length > 0 ? (
                      outgoingRequests.map((req) => (
                        <div key={req.id} data-outgoing-request-card>
                          <ConnectionRequestRow
                            request={req}
                            type="sent"
                            onAction={(reqId, newState) => handleOutgoingStateChange(reqId, newState)}
                          />
                        </div>
                      ))
                    ) : (
                      <div
                        className="friend-empty friend-empty--compact"
                        data-outgoing-empty
                        style={{ padding: '32px 20px', textAlign: 'center' }}
                      >
                        <p style={{ margin: 0, color: 'var(--color-text-secondary, #64748b)' }}>No pending sent connection requests</p>
                      </div>
                    )}
                  </div>
                </section>
              )}
            </>
          )}
        </div>
      </main>

      <FeedRightSidebar />
    </>
  );
}

export default FriendRequestsPage;

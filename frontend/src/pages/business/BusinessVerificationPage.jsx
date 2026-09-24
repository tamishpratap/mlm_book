import { useState, useEffect, useCallback, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  BadgeCheck,
  ArrowLeft,
  Shield,
  Clock,
  AlertCircle,
  FileUp,
  Upload,
  History,
  FileText,
  CheckCircle2,
  XCircle,
} from 'lucide-react';
import businessApi from '../../api/businessApi';

const DOCUMENT_TYPES = [
  { value: 'business_registration', label: 'Business Registration Certificate (LLC / Inc / Private Ltd)' },
  { value: 'gst', label: 'GST / Tax Identification Certificate' },
  { value: 'license', label: 'Official Business License' },
  { value: 'govt_id', label: 'Owner Government-Issued ID / Passport' },
  { value: 'other', label: 'Other Official Document' },
];

function formatDocType(type) {
  const match = DOCUMENT_TYPES.find((d) => d.value === type);
  if (match) return match.label.split(' (')[0];
  return (type || '').replace(/_/g, ' ').toUpperCase();
}

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function BusinessVerificationPage() {
  const { slug } = useParams();

  const [pageData, setPageData] = useState(null);
  const [verifications, setVerifications] = useState([]);
  const [latestVerification, setLatestVerification] = useState(null);
  const [status, setStatus] = useState('not_verified');

  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Form state
  const [documentType, setDocumentType] = useState('business_registration');
  const [documentNumber, setDocumentNumber] = useState('');
  const [documentFile, setDocumentFile] = useState(null);
  const [filePreview, setFilePreview] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState(null);
  const [formSuccess, setFormSuccess] = useState(null);

  const fileInputRef = useRef(null);

  const fetchVerificationData = useCallback(() => {
    return businessApi.getVerification(slug);
  }, [slug]);

  useEffect(() => {
    let isMounted = true;

    fetchVerificationData()
      .then((res) => {
        if (isMounted && res && res.success) {
          setPageData(res.business_page);
          setVerifications(res.verifications || []);
          setLatestVerification(res.latest_verification || null);
          setStatus(res.status || 'not_verified');
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load business verification details.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchVerificationData]);

  const handleFileChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Max 10MB validation
    if (file.size > 10 * 1024 * 1024) {
      setFormError('Document file must be 10MB or less.');
      return;
    }

    setDocumentFile(file);
    setFormError(null);

    if (file.type.startsWith('image/')) {
      setFilePreview(URL.createObjectURL(file));
    } else {
      setFilePreview(null);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (isSubmitting || !documentFile) return;

    setIsSubmitting(true);
    setFormError(null);
    setFormSuccess(null);

    const formData = new FormData();
    formData.append('document_type', documentType);
    if (documentNumber.trim()) {
      formData.append('document_number', documentNumber.trim());
    }
    formData.append('document_file', documentFile);

    try {
      const res = await businessApi.submitVerification(slug, formData);
      if (res && res.success) {
        setFormSuccess(res.message || 'Verification document submitted successfully!');
        setStatus('pending');
        setDocumentFile(null);
        setDocumentNumber('');
        setFilePreview(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
        fetchVerificationData().then((data) => {
          if (data && data.success) {
            setVerifications(data.verifications || []);
            setLatestVerification(data.latest_verification || null);
            setStatus(data.status || 'pending');
          }
        });
      }
    } catch (err) {
      setFormError(err.response?.data?.message || 'Failed to submit verification request.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '80px', color: 'var(--color-text-secondary)' }}>
        Loading business verification portal...
      </div>
    );
  }

  if (error) {
    return (
      <div className="card" style={{ maxWidth: '640px', margin: '40px auto', padding: '32px', textAlign: 'center' }}>
        <AlertCircle size={40} color="#dc2626" style={{ margin: '0 auto 12px auto' }} />
        <h2 style={{ fontSize: '18px', color: '#111827', marginBottom: '8px' }}>Access Denied or Page Not Found</h2>
        <p style={{ color: '#687386', marginBottom: '20px', fontSize: '14px' }}>{error}</p>
        <Link to={`/member/business-pages/${slug}`} className="member-button member-button--secondary">
          <ArrowLeft size={16} />
          <span>Back to Business Page</span>
        </Link>
      </div>
    );
  }

  const businessPage = pageData || {};

  return (
    <div className="biz-page" style={{ width: '100%', margin: '16px auto', padding: '0' }}>
      {/* Header */}
      <header className="biz-header" style={{ marginBottom: '20px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div className="biz-header__info">
          <h1 style={{ display: 'flex', alignItems: 'center', gap: '8px', fontSize: '22px', fontWeight: 800, margin: '0 0 4px 0' }}>
            <BadgeCheck size={26} color="#20c875" />
            <span>Business Verification Portal</span>
          </h1>
          <p style={{ margin: 0, color: '#687386', fontSize: '13.5px' }}>
            Verify {businessPage.page_name} to gain official trust badge, priority discovery ranking, and customer confidence.
          </p>
        </div>
        <div className="biz-header__actions">
          <Link to={`/member/business-pages/${slug}`} className="member-button member-button--secondary">
            <ArrowLeft size={16} />
            <span>Back to Profile</span>
          </Link>
        </div>
      </header>

      <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
        {/* Verification Status Card */}
        <div className="biz-info-card" style={{ padding: '28px', textAlign: 'center', background: '#fff', borderRadius: '16px', border: '1px solid #e5e7eb' }}>
          {status === 'verified' ? (
            <div>
              <div style={{ width: '64px', height: '64px', borderRadius: '50%', background: 'rgba(32, 200, 117, 0.15)', color: '#20c875', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <BadgeCheck size={36} />
              </div>
              <h2 style={{ fontSize: '22px', fontWeight: 800, color: '#1d2738', margin: '0 0 6px 0' }}>
                Official Verified Business Page
              </h2>
              <p style={{ fontSize: '14px', color: '#687386', margin: 0, maxWidth: '520px', marginLeft: 'auto', marginRight: 'auto' }}>
                Congratulations! Your business page has been officially verified by the platform moderation team. The verified badge is now active on your public profile and marketplace listings.
              </p>
            </div>
          ) : status === 'pending' ? (
            <div>
              <div style={{ width: '64px', height: '64px', borderRadius: '50%', background: 'rgba(247, 185, 64, 0.15)', color: '#f7b940', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <Clock size={36} />
              </div>
              <h2 style={{ fontSize: '22px', fontWeight: 800, color: '#1d2738', margin: '0 0 6px 0' }}>
                Verification Pending Review
              </h2>
              <p style={{ fontSize: '14px', color: '#687386', margin: 0, maxWidth: '520px', marginLeft: 'auto', marginRight: 'auto' }}>
                Your submitted documents are currently undergoing review by our compliance team. Reviews typically take 24–48 hours. We will notify you once verified.
              </p>
            </div>
          ) : status === 'rejected' ? (
            <div>
              <div style={{ width: '64px', height: '64px', borderRadius: '50%', background: 'rgba(239, 68, 68, 0.15)', color: '#ef4444', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <XCircle size={36} />
              </div>
              <h2 style={{ fontSize: '22px', fontWeight: 800, color: '#1d2738', margin: '0 0 6px 0' }}>
                Verification Request Not Approved
              </h2>
              <p style={{ fontSize: '14px', color: '#687386', margin: 0, maxWidth: '520px', marginLeft: 'auto', marginRight: 'auto' }}>
                {latestVerification?.admin_notes
                  ? `Feedback: "${latestVerification.admin_notes}"`
                  : 'Your previous verification application was not approved. Please review the guidelines below and submit updated documentation.'}
              </p>
            </div>
          ) : (
            <div>
              <div style={{ width: '64px', height: '64px', borderRadius: '50%', background: 'rgba(79, 125, 243, 0.1)', color: '#4f7df3', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <Shield size={36} />
              </div>
              <h2 style={{ fontSize: '22px', fontWeight: 800, color: '#1d2738', margin: '0 0 6px 0' }}>
                Get Verified Badge
              </h2>
              <p style={{ fontSize: '14px', color: '#687386', margin: 0, maxWidth: '520px', marginLeft: 'auto', marginRight: 'auto' }}>
                Submit official registration documents to verify your business identity and unlock verified features.
              </p>
            </div>
          )}
        </div>

        {/* Verification Document Upload Form (If not verified and not pending) */}
        {status !== 'verified' && status !== 'pending' && (
          <div className="biz-info-card" style={{ padding: '24px', background: '#fff', borderRadius: '16px', border: '1px solid #e5e7eb' }}>
            <h3 className="biz-info-card__title" style={{ fontSize: '16px', fontWeight: 700, margin: '0 0 16px 0', display: 'flex', alignItems: 'center', gap: '8px' }}>
              <FileUp size={20} color="#4f7df3" />
              <span>Submit Verification Document</span>
            </h3>

            {formSuccess && (
              <div style={{ padding: '12px 16px', background: '#dcfce7', color: '#15803d', borderRadius: '10px', marginBottom: '16px', fontSize: '13.5px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                <CheckCircle2 size={18} />
                <span>{formSuccess}</span>
              </div>
            )}

            {formError && (
              <div style={{ padding: '12px 16px', background: '#fee2e2', color: '#b91c1c', borderRadius: '10px', marginBottom: '16px', fontSize: '13.5px' }}>
                {formError}
              </div>
            )}

            <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
              {/* Document Type */}
              <div className="form-group">
                <label style={{ fontSize: '13.5px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  Document Type <span style={{ color: '#ef4444' }}>*</span>
                </label>
                <select
                  value={documentType}
                  onChange={(e) => setDocumentType(e.target.value)}
                  className="biz-filter-select"
                  style={{ width: '100%', padding: '10px 12px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px' }}
                  required
                >
                  {DOCUMENT_TYPES.map((d) => (
                    <option key={d.value} value={d.value}>
                      {d.label}
                    </option>
                  ))}
                </select>
              </div>

              {/* Document Number */}
              <div className="form-group">
                <label style={{ fontSize: '13.5px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  Registration / Document Number (Optional)
                </label>
                <input
                  type="text"
                  value={documentNumber}
                  onChange={(e) => setDocumentNumber(e.target.value)}
                  placeholder="e.g. REG-98472910, GSTIN12345, LIC-5849..."
                  maxLength={100}
                  className="biz-search-input"
                  style={{ width: '100%', padding: '10px 12px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px' }}
                />
              </div>

              {/* Document File */}
              <div className="form-group">
                <label style={{ fontSize: '13.5px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  Document File (PDF, PNG, JPG, WEBP, Max 10MB) <span style={{ color: '#ef4444' }}>*</span>
                </label>
                <input
                  type="file"
                  ref={fileInputRef}
                  accept=".pdf,.png,.jpg,.jpeg,.webp"
                  onChange={handleFileChange}
                  required
                  style={{ width: '100%', padding: '10px 12px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '13.5px', background: '#f9fafb' }}
                />

                {documentFile && (
                  <div style={{ marginTop: '8px', display: 'flex', alignItems: 'center', gap: '8px', fontSize: '13px', color: '#4f7df3' }}>
                    <FileText size={16} />
                    <span>Selected: {documentFile.name} ({(documentFile.size / 1024 / 1024).toFixed(2)} MB)</span>
                  </div>
                )}

                {filePreview && (
                  <div style={{ marginTop: '10px', maxWidth: '240px', maxHeight: '160px', overflow: 'hidden', borderRadius: '8px', border: '1px solid #e5e7eb' }}>
                    <img src={filePreview} alt="Preview" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                  </div>
                )}
              </div>

              {/* Actions */}
              <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '10px' }}>
                <button
                  type="submit"
                  className="member-button member-button--primary"
                  disabled={isSubmitting || !documentFile}
                  style={{ padding: '10px 22px', fontSize: '14px' }}
                >
                  <Upload size={16} />
                  <span>{isSubmitting ? 'Submitting Application...' : 'Submit for Verification'}</span>
                </button>
              </div>
            </form>
          </div>
        )}

        {/* Verification History Matrix */}
        {verifications.length > 0 && (
          <div className="biz-info-card" style={{ padding: '20px', background: '#fff', borderRadius: '16px', border: '1px solid #e5e7eb' }}>
            <h3 className="biz-info-card__title" style={{ fontSize: '15px', fontWeight: 700, margin: '0 0 14px 0', display: 'flex', alignItems: 'center', gap: '8px' }}>
              <History size={18} color="#4f7df3" />
              <span>Verification Submission History</span>
            </h3>

            <div style={{ overflowX: 'auto' }}>
              <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '13px' }}>
                <thead>
                  <tr style={{ borderBottom: '2px solid #e7ecf4', textAlign: 'left', color: '#687386' }}>
                    <th style={{ padding: '10px' }}>Document Type</th>
                    <th style={{ padding: '10px' }}>Document No.</th>
                    <th style={{ padding: '10px' }}>Submitted On</th>
                    <th style={{ padding: '10px', textAlign: 'center' }}>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {verifications.map((v) => (
                    <tr key={v.id} style={{ borderBottom: '1px solid #f1f5f9' }}>
                      <td style={{ padding: '10px', fontWeight: 600, color: '#1d2738' }}>
                        {formatDocType(v.document_type)}
                      </td>
                      <td style={{ padding: '10px', color: '#687386' }}>
                        {v.document_number || 'N/A'}
                      </td>
                      <td style={{ padding: '10px', color: '#98a2b3' }}>
                        {formatDate(v.created_at)}
                      </td>
                      <td style={{ padding: '10px', textAlign: 'center' }}>
                        {v.status === 'verified' || v.status === 'approved' ? (
                          <span className="biz-badge biz-badge--verified" style={{ fontSize: '10px' }}>
                            Verified
                          </span>
                        ) : v.status === 'pending' ? (
                          <span
                            className="biz-badge"
                            style={{ fontSize: '10px', background: 'rgba(247, 185, 64, 0.15)', color: '#b7791f' }}
                          >
                            Pending Review
                          </span>
                        ) : v.status === 'rejected' ? (
                          <span className="biz-badge biz-badge--unverified" style={{ fontSize: '10px', background: 'rgba(239, 68, 68, 0.15)', color: '#dc2626' }}>
                            Rejected
                          </span>
                        ) : (
                          <span className="biz-badge biz-badge--unverified" style={{ fontSize: '10px' }}>
                            {v.status}
                          </span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default BusinessVerificationPage;

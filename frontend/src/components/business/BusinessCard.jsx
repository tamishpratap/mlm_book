import { Link } from 'react-router-dom';
import {
  Tag,
  Lock,
  FileText,
  Globe,
  MapPin,
  Calendar,
  Eye,
  Edit,
  Trash2,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import VerifiedBadge from '../common/VerifiedBadge';
import { getAvatarUrl, getCoverUrl, getInitials } from '../../utils/assetHelper';

function formatDate(dateString) {
  if (!dateString) return '';
  const d = new Date(dateString);
  return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

export function BusinessCard({ businessPage, onDelete = null }) {
  const { user: currentUser } = useAuth();
  if (!businessPage) return null;

  const currentMemberId = currentUser?.id;
  const isOwner = businessPage.member_id === currentMemberId;

  const coverUrl = businessPage.cover_photo ? getCoverUrl(businessPage.cover_photo) : null;
  const logoUrl = businessPage.logo ? getAvatarUrl(businessPage.logo) : null;

  const locationText = [businessPage.city, businessPage.state, businessPage.country]
    .filter(Boolean)
    .join(', ');

  const handleDelete = (e) => {
    e.preventDefault();
    if (window.confirm(`Are you sure you want to delete "${businessPage.page_name}"?`)) {
      if (onDelete) {
        onDelete(businessPage.slug);
      }
    }
  };

  return (
    <article className="biz-card">
      <div className="biz-card__cover">
        {coverUrl && (
          <img src={coverUrl} alt={`${businessPage.page_name} cover`} loading="lazy" />
        )}
      </div>

      <div className="biz-card__body">
        <div className="biz-card__avatar">
          {logoUrl ? (
            <img src={logoUrl} alt={`${businessPage.page_name} logo`} loading="lazy" />
          ) : (
            <span>{getInitials(businessPage.page_name)}</span>
          )}
        </div>

        <div className="biz-card__header">
          <h3 className="biz-card__title">
            <Link to={`/member/business-pages/${businessPage.slug}`} className="biz-card__title-link" style={{ display: 'inline-flex', alignItems: 'center' }}>
              <span>{businessPage.page_name}</span>
              <VerifiedBadge member={businessPage.member || { is_verified: Boolean(businessPage.is_verified) }} size={16} />
            </Link>
          </h3>
          <span className="biz-card__username">@{businessPage.page_username}</span>
        </div>

        <div className="biz-card__badges">
          <span className="biz-badge biz-badge--category">
            <Tag size={12} aria-hidden="true" />
            <span>{businessPage.category}</span>
          </span>
          <span className="biz-badge biz-badge--visibility">
            {businessPage.visibility === 'private' ? (
              <>
                <Lock size={12} aria-hidden="true" />
                <span>Private</span>
              </>
            ) : businessPage.visibility === 'draft' ? (
              <>
                <FileText size={12} aria-hidden="true" />
                <span>Draft</span>
              </>
            ) : (
              <>
                <Globe size={12} aria-hidden="true" />
                <span>Public</span>
              </>
            )}
          </span>
        </div>

        {businessPage.description && (
          <p className="biz-card__description">{businessPage.description}</p>
        )}

        <div className="biz-card__meta">
          {locationText && (
            <div className="biz-card__meta-item">
              <MapPin size={13} aria-hidden="true" />
              <span className="text-truncate">{locationText}</span>
            </div>
          )}
          <div className="biz-card__meta-item">
            <Calendar size={13} aria-hidden="true" />
            <span>Created {formatDate(businessPage.created_at)}</span>
          </div>
        </div>

        <div className="biz-card__actions">
          <Link
            to={`/member/business-pages/${businessPage.slug}`}
            className="member-button member-button--primary biz-card__btn-view"
          >
            <Eye size={14} aria-hidden="true" />
            <span>View</span>
          </Link>

          {isOwner && (
            <>
              <Link
                to={`/member/business-pages/${businessPage.slug}/edit`}
                className="member-button member-button--secondary biz-card__action-btn biz-card__action-btn--edit"
                title="Edit Page"
                aria-label={`Edit ${businessPage.page_name}`}
              >
                <Edit size={14} aria-hidden="true" />
              </Link>

              <button
                type="button"
                className="member-button member-button--danger biz-card__action-btn biz-card__action-btn--delete"
                title="Delete Page"
                aria-label={`Delete ${businessPage.page_name}`}
                onClick={handleDelete}
              >
                <Trash2 size={14} aria-hidden="true" />
              </button>
            </>
          )}
        </div>
      </div>
    </article>
  );
}

export default BusinessCard;

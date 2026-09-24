import { useState } from 'react';
import {
  X,
  Wallet,
  DollarSign,
  AlertCircle,
  CheckCircle2,
  Sparkles,
  ArrowRight,
  RefreshCw,
  Megaphone,
} from 'lucide-react';
import businessApi from '../../../api/businessApi';
import { ModalPortal } from '../../common/ModalPortal';

const TOPUP_PRESETS = [5, 10, 25, 50, 100];

export function AddFundsToCampaignModal({
  page,
  campaign,
  availableAdFunds = 0.0,
  platformFeePercent = 2.5,
  onClose,
  onCampaignUpdated,
  onOpenDepositModal,
}) {
  const [amount, setAmount] = useState('10');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  if (!campaign) return null;

  const numAmount = parseFloat(amount) || 0;
  const numFeePercent = Number(platformFeePercent) || 2.5;
  const feeAmount = parseFloat((numAmount * (numFeePercent / 100)).toFixed(2));
  const totalWalletDebit = parseFloat((numAmount + feeAmount).toFixed(2));
  const hasInsufficientFunds = availableAdFunds !== undefined && totalWalletDebit > Number(availableAdFunds);
  const shortfall = hasInsufficientFunds ? (totalWalletDebit - Number(availableAdFunds)).toFixed(2) : '0.00';

  const origBudget = parseFloat(campaign.budget) || 0;
  const addFunds = parseFloat(campaign.additional_funding) || 0;
  const totalFunded = parseFloat(campaign.total_funded) || (origBudget + addFunds);
  const spent = parseFloat(campaign.spent_amount) || 0;
  const remaining = parseFloat(campaign.remaining_amount) || Math.max(0, totalFunded - spent);

  const handleSubmit = async (e) => {
    e?.preventDefault();
    setErrorMsg('');
    setSuccessMsg('');

    if (isNaN(numAmount) || numAmount < 1.0) {
      setErrorMsg('Please enter a valid top-up amount (minimum $1.00 USD).');
      return;
    }

    if (hasInsufficientFunds) {
      setErrorMsg(
        `Insufficient advertising funds. Total Required: $${totalWalletDebit.toFixed(2)} USD, Available: $${Number(availableAdFunds).toFixed(2)} USD. Shortfall: $${shortfall} USD.`
      );
      return;
    }

    setIsSubmitting(true);

    try {
      const cId = campaign.campaign_id || campaign.id;
      const res = await businessApi.addFundsToAdCampaign(page.slug, cId, {
        amount: numAmount,
      });

      if (res.success) {
        setSuccessMsg(res.message || 'Campaign funds added successfully!');
        if (onCampaignUpdated) {
          onCampaignUpdated(res.campaign);
        }
        setTimeout(() => {
          onClose();
        }, 1200);
      } else {
        setErrorMsg(res.message || 'Failed to add funds to campaign.');
      }
    } catch (err) {
      console.error('Failed to add funds to campaign:', err);
      const backendMsg =
        err.response?.data?.message ||
        (err.response?.data?.errors?.amount ? err.response.data.errors.amount[0] : null) ||
        'Failed to add funds. Please try again.';
      setErrorMsg(backendMsg);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <ModalPortal isOpen={Boolean(campaign)} onClose={onClose}>
      <div
        className="card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-funds-campaign-title"
        style={{
          backgroundColor: '#ffffff',
          borderRadius: '20px',
          maxWidth: '560px',
          width: '100%',
          maxHeight: 'min(90vh, 760px)',
          overflowY: 'auto',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          padding: '24px',
          position: 'relative',
        }}
      >
        {/* Close Button */}
        <button
          type="button"
          onClick={onClose}
          style={{
            position: 'absolute',
            top: '20px',
            right: '20px',
            background: '#f1f5f9',
            border: 'none',
            borderRadius: '50%',
            width: '32px',
            height: '32px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            cursor: 'pointer',
            color: '#64748b',
          }}
          aria-label="Close"
        >
          <X size={18} />
        </button>

        {/* Modal Header */}
        <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '18px' }}>
          <div
            style={{
              width: '44px',
              height: '44px',
              borderRadius: '12px',
              backgroundColor: '#f0fdf4',
              border: '1px solid #bbf7d0',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: '#16a34a',
            }}
          >
            <Wallet size={22} />
          </div>
          <div>
            <h3 style={{ margin: 0, fontSize: '18px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)' }}>
              Add Funds to Campaign
            </h3>
            <p style={{ margin: '2px 0 0', fontSize: '12.5px', color: 'var(--color-text-secondary, #64748b)' }}>
              Top up running budget and reactivate ad delivery on this exact campaign
            </p>
          </div>
        </div>

        {/* Campaign Info Card */}
        <div
          style={{
            padding: '14px 16px',
            borderRadius: '14px',
            backgroundColor: '#f8fafc',
            border: '1px solid #e2e8f0',
            marginBottom: '18px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
            <span style={{ fontSize: '13.5px', fontWeight: 700, color: '#1e293b' }}>
              {campaign.campaign_name}
            </span>
            <span
              style={{
                fontSize: '11px',
                fontWeight: 700,
                padding: '2px 8px',
                borderRadius: '8px',
                backgroundColor: campaign.is_budget_exhausted ? '#fee2e2' : campaign.is_budget_low ? '#fef3c7' : '#dcfce7',
                color: campaign.is_budget_exhausted ? '#b91c1c' : campaign.is_budget_low ? '#b45309' : '#15803d',
              }}
            >
              {campaign.display_status || campaign.status}
            </span>
          </div>

          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(3, 1fr)',
              gap: '10px',
              textAlign: 'center',
              paddingTop: '8px',
              borderTop: '1px solid #e2e8f0',
            }}
          >
            <div>
              <div style={{ fontSize: '10.5px', color: '#64748b', fontWeight: 600 }}>Original Budget</div>
              <div style={{ fontSize: '13px', fontWeight: 800, color: '#4f7df3' }}>
                ${origBudget.toFixed(2)}
              </div>
            </div>
            <div>
              <div style={{ fontSize: '10.5px', color: '#64748b', fontWeight: 600 }}>Spent (Rewards)</div>
              <div style={{ fontSize: '13px', fontWeight: 800, color: '#dc2626' }}>
                ${spent.toFixed(2)}
              </div>
            </div>
            <div>
              <div style={{ fontSize: '10.5px', color: '#64748b', fontWeight: 600 }}>Current Running</div>
              <div style={{ fontSize: '13px', fontWeight: 800, color: '#15803d' }}>
                ${remaining.toFixed(2)}
              </div>
            </div>
          </div>
        </div>

        {/* Feedback Messages */}
        {errorMsg && (
          <div
            style={{
              padding: '12px 14px',
              borderRadius: '12px',
              backgroundColor: '#fef2f2',
              border: '1px solid #fecaca',
              color: '#b91c1c',
              fontSize: '13px',
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              marginBottom: '16px',
            }}
          >
            <AlertCircle size={16} style={{ flexShrink: 0 }} />
            <span>{errorMsg}</span>
          </div>
        )}

        {successMsg && (
          <div
            style={{
              padding: '12px 14px',
              borderRadius: '12px',
              backgroundColor: '#f0fdf4',
              border: '1px solid #bbf7d0',
              color: '#15803d',
              fontSize: '13px',
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              marginBottom: '16px',
            }}
          >
            <CheckCircle2 size={16} style={{ flexShrink: 0 }} />
            <span>{successMsg}</span>
          </div>
        )}

        {/* Form */}
        <form onSubmit={handleSubmit}>
          {/* Top-up Input */}
          <div style={{ marginBottom: '16px' }}>
            <label style={{ display: 'block', fontSize: '13px', fontWeight: 700, color: '#334155', marginBottom: '6px' }}>
              Add Funds Amount (USD)
            </label>
            <div style={{ position: 'relative' }}>
              <span
                style={{
                  position: 'absolute',
                  left: '14px',
                  top: '50%',
                  transform: 'translateY(-50%)',
                  fontWeight: 700,
                  fontSize: '15px',
                  color: '#64748b',
                }}
              >
                $
              </span>
              <input
                type="number"
                step="0.01"
                min="1"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
                placeholder="10.00"
                style={{
                  width: '100%',
                  padding: '10px 14px 10px 32px',
                  borderRadius: '10px',
                  border: '1px solid #cbd5e1',
                  fontSize: '15px',
                  fontWeight: 700,
                  color: '#1e293b',
                  outline: 'none',
                }}
              />
            </div>

            {/* Presets */}
            <div style={{ display: 'flex', gap: '8px', marginTop: '8px', flexWrap: 'wrap' }}>
              {TOPUP_PRESETS.map((val) => (
                <button
                  key={val}
                  type="button"
                  onClick={() => setAmount(String(val))}
                  style={{
                    padding: '5px 12px',
                    borderRadius: '8px',
                    fontSize: '12px',
                    fontWeight: 700,
                    border: numAmount === val ? '1px solid #16a34a' : '1px solid #e2e8f0',
                    backgroundColor: numAmount === val ? '#f0fdf4' : '#ffffff',
                    color: numAmount === val ? '#16a34a' : '#475569',
                    cursor: 'pointer',
                    transition: 'all 0.15s',
                  }}
                >
                  ${val}
                </button>
              ))}
            </div>
          </div>

          {/* Cost & Balance Summary */}
          <div
            style={{
              padding: '14px',
              borderRadius: '12px',
              backgroundColor: '#f8fafc',
              border: '1px solid #e2e8f0',
              marginBottom: '20px',
            }}
          >
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12.5px', marginBottom: '6px', color: '#64748b' }}>
              <span>Top-up Budget Addition:</span>
              <span style={{ fontWeight: 700, color: '#1e293b' }}>${numAmount.toFixed(2)} USD</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12.5px', marginBottom: '6px', color: '#64748b' }}>
              <span>Platform Fee ({numFeePercent}%):</span>
              <span style={{ fontWeight: 700, color: '#1e293b' }}>${feeAmount.toFixed(2)} USD</span>
            </div>
            <div
              style={{
                display: 'flex',
                justifyContent: 'space-between',
                fontSize: '13px',
                paddingTop: '6px',
                borderTop: '1px dashed #cbd5e1',
                color: '#334155',
                fontWeight: 700,
              }}
            >
              <span>Total Wallet Debit:</span>
              <span style={{ color: '#16a34a', fontWeight: 800 }}>${totalWalletDebit.toFixed(2)} USD</span>
            </div>

            <div
              style={{
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                marginTop: '10px',
                paddingTop: '8px',
                borderTop: '1px solid #e2e8f0',
                fontSize: '12.5px',
              }}
            >
              <span style={{ color: '#64748b' }}>Available Ad Balance:</span>
              <span
                style={{
                  fontWeight: 800,
                  color: hasInsufficientFunds ? '#b91c1c' : '#15803d',
                }}
              >
                ${Number(availableAdFunds || 0).toFixed(2)} USD
              </span>
            </div>

            {hasInsufficientFunds && (
              <div
                style={{
                  marginTop: '10px',
                  padding: '8px 10px',
                  borderRadius: '8px',
                  backgroundColor: '#fef2f2',
                  border: '1px solid #fecaca',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                }}
              >
                <span style={{ fontSize: '12px', color: '#b91c1c', fontWeight: 600 }}>
                  Shortfall: ${shortfall} USD
                </span>
                {onOpenDepositModal && (
                  <button
                    type="button"
                    onClick={() => {
                      onClose();
                      onOpenDepositModal();
                    }}
                    style={{
                      background: 'none',
                      border: 'none',
                      color: '#4f7df3',
                      fontWeight: 700,
                      fontSize: '12px',
                      cursor: 'pointer',
                      padding: 0,
                      textDecoration: 'underline',
                    }}
                  >
                    Deposit Funds Now
                  </button>
                )}
              </div>
            )}
          </div>

          {/* Action Buttons */}
          <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end' }}>
            <button
              type="button"
              className="btn btn-secondary"
              onClick={onClose}
              style={{
                padding: '9px 18px',
                borderRadius: '10px',
                fontWeight: 600,
                fontSize: '13px',
              }}
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isSubmitting || hasInsufficientFunds || numAmount < 1.0}
              className="btn btn-primary"
              style={{
                padding: '9px 22px',
                borderRadius: '10px',
                fontWeight: 700,
                fontSize: '13.5px',
                backgroundColor: '#16a34a',
                color: '#ffffff',
                border: 'none',
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
                cursor: (isSubmitting || hasInsufficientFunds || numAmount < 1.0) ? 'not-allowed' : 'pointer',
                opacity: (isSubmitting || hasInsufficientFunds || numAmount < 1.0) ? 0.6 : 1,
              }}
            >
              {isSubmitting ? (
                <>
                  <RefreshCw size={15} className="animate-spin" />
                  <span>Processing...</span>
                </>
              ) : (
                <>
                  <Wallet size={15} />
                  <span>Add Funds & Reactivate</span>
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </ModalPortal>
  );
}

export default AddFundsToCampaignModal;
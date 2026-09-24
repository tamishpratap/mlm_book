/**
 * MLM Book — Member Email OTP Verification JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {
    const otpInput = document.getElementById('otpInput');
    const verifyForm = document.getElementById('memberVerifyForm');
    const submitBtn = document.getElementById('verifySubmitBtn');
    const resendBtn = document.getElementById('resendOtpBtn');
    const cooldownTimer = document.getElementById('cooldownTimer');
    const resendBtnText = document.getElementById('resendBtnText');

    // 1. Focus input automatically
    if (otpInput) {
        otpInput.focus();

        // 2. Format input to numbers only, max 6 digits
        otpInput.addEventListener('input', function (e) {
            let val = e.target.value.replace(/\D/g, '').slice(0, 6);
            e.target.value = val;

            if (val.length === 6) {
                // Highlight container
                const container = otpInput.closest('.member-verify-otp-container');
                if (container) {
                    container.classList.add('is-filled');
                }
            }
        });

        // 3. Handle Paste
        otpInput.addEventListener('paste', function (e) {
            e.preventDefault();
            const pastedData = (e.clipboardData || window.clipboardData).getData('text');
            const cleanDigits = pastedData.replace(/\D/g, '').slice(0, 6);
            otpInput.value = cleanDigits;

            if (cleanDigits.length === 6 && verifyForm) {
                // small delay for smooth visual feedback
                setTimeout(() => {
                    verifyForm.submit();
                }, 200);
            }
        });
    }

    // 4. Form submission state
    if (verifyForm && submitBtn) {
        verifyForm.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.classList.add('is-loading');
            submitBtn.querySelector('span').textContent = 'Verifying…';
        });
    }

    // 5. Resend Cooldown Countdown
    if (resendBtn && cooldownTimer) {
        let secondsLeft = parseInt(resendBtn.getAttribute('data-cooldown') || '0', 10);

        if (secondsLeft > 0) {
            resendBtn.disabled = true;

            const interval = setInterval(function () {
                secondsLeft--;
                if (secondsLeft > 0) {
                    cooldownTimer.textContent = secondsLeft;
                } else {
                    clearInterval(interval);
                    resendBtn.disabled = false;
                    resendBtn.removeAttribute('data-cooldown');
                    resendBtnText.textContent = 'Resend Code';
                }
            }, 1000);
        }
    }
});

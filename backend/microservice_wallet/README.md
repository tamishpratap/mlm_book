# Zero-Cost Serverless Wallet Microservice (Vercel / Render)

Iss microservice se aap apna Web3/Node.js script **100% Free** me host kar sakte hain, **Hostinger upgrade ki koi zarurat nahi padegi**.

---

### Step 1: Free Vercel Deployment (Only 2 Minutes)
1. [vercel.com](https://vercel.com) par free account banayein (GitHub account se sign in karein).
2. GitHub par ek new private repository banayein, jaise: `redcoin-wallet-service`.
3. Iss `microservice_wallet` folder ki sabhi files (`package.json`, `vercel.json`, `api/process-wallet.js`) uss GitHub repo me push kar dein.
4. Vercel dashboard me jakar **Add New Project** -> Uss GitHub repo ko select karein -> **Deploy** par click karein.
5. Deployment hote hi aapko ek URL mil jayega, jaise:
   `https://redcoin-wallet-service.vercel.app`

---

### Step 2: Vercel Environment Variables
Vercel Project -> **Settings** -> **Environment Variables** me add karein:
- `WALLET_SECRET_KEY`: `RedCoinCronKey2026!` (ya aapka koi bhi strong secret)
- `ADMIN_PRIVATE_KEY`: Aapka Admin Wallet Private Key (Safe & Encrypted)

---

### Step 3: Laravel `.env` Configuration
Hostinger par apne Laravel project ke `.env` file me ye 2 lines add kar dein:
```env
WALLET_MICROSERVICE_URL=https://redcoin-wallet-service.vercel.app/api/process-wallet
WALLET_MICROSERVICE_KEY=RedCoinCronKey2026!
```

Bas! Ab jab bhi `autopayWithdrawal` chalega:
1. Laravel Hostinger se Vercel ko secure HTTPS POST request bhejega.
2. Vercel par aapka `ethers.js` script run hoga aur blockchain transaction process karega.
3. Response direct Laravel me aakar database me `Approved` aur `txHash` save ho jayega.
4. **Hostinger Shared hosting par 0 CPU load aur koi upgrade nahi!**

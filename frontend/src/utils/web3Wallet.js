/**
 * Web3 Wallet & BEP-20 Transfer Helper for Member User Panel
 * EIP-1193 standard compliant. Supports MetaMask, Trust Wallet, Binance Web3 Wallet, OKX, etc.
 */

export const BSC_CHAINS = {
  mainnet: {
    chainId: 56,
    chainIdHex: '0x38',
    chainName: 'BNB Smart Chain',
    nativeCurrency: { name: 'BNB', symbol: 'BNB', decimals: 18 },
    rpcUrls: ['https://bsc-dataseed.binance.org/', 'https://binance.llamarpc.com'],
    blockExplorerUrls: ['https://bscscan.com/'],
    defaultUsdtContract: '0x55d398326f99059fF775485246999027B3197955',
  },
  testnet: {
    chainId: 97,
    chainIdHex: '0x61',
    chainName: 'BNB Smart Chain Testnet',
    nativeCurrency: { name: 'tBNB', symbol: 'tBNB', decimals: 18 },
    rpcUrls: ['https://data-seed-prebsc-1-s1.binance.org:8545/'],
    blockExplorerUrls: ['https://testnet.bscscan.com/'],
    defaultUsdtContract: '0x337610d27c682E347C9cD60BD4b3b107C9d34dDd',
  },
};

/**
 * Check if an EIP-1193 provider (MetaMask or EVM browser wallet) is present.
 */
export function hasEthereumProvider() {
  return typeof window !== 'undefined' && Boolean(window.ethereum);
}

/**
 * Convert technical/raw Web3 and JSON-RPC errors into clean, user-friendly messages.
 */
export function formatWeb3Error(err) {
  if (!err) return 'An unexpected wallet error occurred. Please try again.';

  const code = err?.code || err?.error?.code;
  const rawMsg = String(err?.message || err?.data?.message || err?.error?.message || err || '').toLowerCase();

  // 1. Pending request in extension popup / window (-32002)
  if (
    code === -32002 ||
    rawMsg.includes('already pending') ||
    rawMsg.includes('wallet_requestpermissions') ||
    rawMsg.includes('already processing') ||
    rawMsg.includes('resource unavailable')
  ) {
    return 'A connection request is already open in your wallet extension. Please open your MetaMask or Web3 wallet extension icon in your browser toolbar to approve or dismiss it.';
  }

  // 2. User rejected / cancelled (4001)
  if (
    code === 4001 ||
    rawMsg.includes('user rejected') ||
    rawMsg.includes('user denied') ||
    rawMsg.includes('cancelled') ||
    rawMsg.includes('declined')
  ) {
    return 'The connection or transaction request was cancelled in your wallet. Click "Connect Wallet" whenever you are ready to continue.';
  }

  // 3. Network addition / chain switch (4902)
  if (
    code === 4902 ||
    rawMsg.includes('wallet_addethereumchain') ||
    rawMsg.includes('unrecognized chain')
  ) {
    return 'BNB Smart Chain is not added to your wallet. Please approve adding the BSC network when prompted in your wallet.';
  }

  // 4. Insufficient funds / gas
  if (
    rawMsg.includes('insufficient funds') ||
    rawMsg.includes('exceeds balance') ||
    rawMsg.includes('gas required exceeds')
  ) {
    return 'Insufficient balance in your wallet. Please ensure you have BNB for network gas fees and sufficient USDT for the deposit.';
  }

  // 5. No provider / wallet extension missing
  if (
    rawMsg.includes('no web3 wallet') ||
    rawMsg.includes('not detected') ||
    rawMsg.includes('ethereum is undefined')
  ) {
    return 'No Web3 wallet extension detected. Please install MetaMask or open this site in an EVM-compatible Web3 browser (e.g. Trust Wallet / Binance Web3).';
  }

  // 6. Wallet locked
  if (rawMsg.includes('locked') || rawMsg.includes('unlock')) {
    return 'Your Web3 wallet is currently locked. Please open your wallet extension and enter your password to unlock it.';
  }

  // 7. Internal JSON-RPC error (-32603)
  if (code === -32603 || rawMsg.includes('internal json-rpc error')) {
    return 'Your wallet could not complete the request. Please verify that your wallet has enough BNB for gas fees and try again.';
  }

  // 8. Reverted transaction
  if (rawMsg.includes('execution reverted') || rawMsg.includes('revert')) {
    return 'Transaction reverted by the smart contract. Please check your USDT balance and token allowance.';
  }

  // Fallback: clean up technical prefixes if any
  const cleanMsg = err?.message ? err.message.replace(/^Error:\s*/i, '').trim() : 'Failed to communicate with Web3 wallet.';
  if (
    cleanMsg.includes('wallet_') ||
    cleanMsg.includes('eth_') ||
    cleanMsg.includes('rpc') ||
    cleanMsg.includes('0x') ||
    cleanMsg.includes('origin http')
  ) {
    return 'Your wallet extension could not process the request. Please open MetaMask/extension to check notifications or refresh and try again.';
  }

  return cleanMsg;
}

/**
 * Connect user's Web3 wallet and return active address.
 */
export async function connectWallet() {
  if (!hasEthereumProvider()) {
    throw new Error('No Web3 wallet detected. Please install MetaMask, Trust Wallet, or another EVM wallet extension.');
  }

  try {
    const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
    if (!accounts || accounts.length === 0) {
      throw new Error('No accounts authorized. Please select an account in your wallet.');
    }
    return accounts[0].toLowerCase();
  } catch (err) {
    throw new Error(formatWeb3Error(err));
  }
}

/**
 * Get currently authorized account without triggering popup.
 */
export async function getConnectedAccount() {
  if (!hasEthereumProvider()) return null;
  try {
    const accounts = await window.ethereum.request({ method: 'eth_accounts' });
    return accounts && accounts.length > 0 ? accounts[0].toLowerCase() : null;
  } catch {
    return null;
  }
}

/**
 * Get current Chain ID as integer.
 */
export async function getCurrentChainId() {
  if (!hasEthereumProvider()) return null;
  try {
    const chainIdHex = await window.ethereum.request({ method: 'eth_chainId' });
    return parseInt(chainIdHex, 16);
  } catch {
    return null;
  }
}

/**
 * Switch wallet network to BNB Smart Chain (Mainnet or Testnet).
 */
export async function switchToBsc(isTestnet = false) {
  if (!hasEthereumProvider()) {
    throw new Error('Web3 wallet is not available. Please install MetaMask or Trust Wallet.');
  }

  const config = isTestnet ? BSC_CHAINS.testnet : BSC_CHAINS.mainnet;

  try {
    await window.ethereum.request({
      method: 'wallet_switchEthereumChain',
      params: [{ chainId: config.chainIdHex }],
    });
    return true;
  } catch (switchError) {
    // Error code 4902 means the chain has not been added to MetaMask
    if (switchError.code === 4902) {
      try {
        await window.ethereum.request({
          method: 'wallet_addEthereumChain',
          params: [
            {
              chainId: config.chainIdHex,
              chainName: config.chainName,
              nativeCurrency: config.nativeCurrency,
              rpcUrls: config.rpcUrls,
              blockExplorerUrls: config.blockExplorerUrls,
            },
          ],
        });
        return true;
      } catch (addError) {
        throw new Error(formatWeb3Error(addError));
      }
    }
    throw new Error(formatWeb3Error(switchError));
  }
}

/**
 * Convert human-readable token amount to uint256 hex string with 18 decimals.
 */
export function amountToUint256Hex(amount, decimals = 18) {
  const parts = String(amount).trim().split('.');
  const wholePart = parts[0] || '0';
  let fractionPart = parts[1] || '';

  if (fractionPart.length > decimals) {
    fractionPart = fractionPart.substring(0, decimals);
  } else {
    fractionPart = fractionPart.padEnd(decimals, '0');
  }

  const cleanWhole = wholePart.replace(/^0+/, '') || '0';
  const fullDecStr = cleanWhole === '0' ? fractionPart.replace(/^0+/, '') || '0' : cleanWhole + fractionPart;

  // Use native BigInt for precision
  const bigIntVal = BigInt(fullDecStr);
  return '0x' + bigIntVal.toString(16).padStart(64, '0');
}

/**
 * Encode ERC-20 transfer(address to, uint256 value) calldata.
 */
export function encodeErc20Transfer(recipientAddress, amount, decimals = 18) {
  const methodId = 'a9059cbb'; // keccak256("transfer(address,uint256)")
  const cleanRecipient = recipientAddress.toLowerCase().replace(/^0x/, '').padStart(64, '0');
  const amountHex = amountToUint256Hex(amount, decimals).replace(/^0x/, '');

  return `0x${methodId}${cleanRecipient}${amountHex}`;
}

/**
 * Send BEP-20 USDT transfer from connected wallet to configured destination wallet.
 * Returns the blockchain-generated transaction hash.
 */
export async function sendBscUsdtTransfer({
  recipientAddress,
  amountUsdt,
  tokenContractAddress,
  isTestnet = false,
}) {
  if (!hasEthereumProvider()) {
    throw new Error('Web3 wallet is not available.');
  }

  const numAmount = parseFloat(amountUsdt);
  if (isNaN(numAmount) || numAmount < 10) {
    throw new Error('Minimum deposit amount is $10.00 USD equivalent.');
  }

  if (!recipientAddress || !recipientAddress.startsWith('0x') || recipientAddress.length !== 42) {
    throw new Error('Platform receiving deposit wallet address is invalid (must be a valid 42-char 0x BEP-20 address). Please update it in Admin Settings.');
  }

  // Ensure connected
  const fromAddress = await connectWallet();

  // Ensure correct network
  const targetChain = isTestnet ? BSC_CHAINS.testnet : BSC_CHAINS.mainnet;
  const currentChainId = await getCurrentChainId();
  if (currentChainId !== targetChain.chainId) {
    await switchToBsc(isTestnet);
  }

  const contractAddress = tokenContractAddress || targetChain.defaultUsdtContract;
  const data = encodeErc20Transfer(recipientAddress, numAmount, 18);

  try {
    // Send transaction through the user's Web3 wallet
    const txHash = await window.ethereum.request({
      method: 'eth_sendTransaction',
      params: [
        {
          from: fromAddress,
          to: contractAddress,
          data: data,
          value: '0x0', // 0 BNB value, purely token transfer
        },
      ],
    });

    if (!txHash || typeof txHash !== 'string' || !txHash.startsWith('0x')) {
      throw new Error('Transaction submission did not return a valid transaction hash.');
    }

    return {
      txHash,
      fromAddress,
      recipientAddress,
      amount: numAmount,
      contractAddress,
      network: 'BEP-20',
      token: 'USDT',
    };
  } catch (err) {
    throw new Error(formatWeb3Error(err));
  }
}

/**
 * Poll for transaction receipt to confirm inclusion in a block.
 */
export async function waitForReceipt(txHash, maxAttempts = 30, intervalMs = 2500) {
  if (!hasEthereumProvider()) return null;

  for (let i = 0; i < maxAttempts; i++) {
    try {
      const receipt = await window.ethereum.request({
        method: 'eth_getTransactionReceipt',
        params: [txHash],
      });

      if (receipt && receipt.blockNumber) {
        return receipt;
      }
    } catch {
      // Ignore transient errors
    }

    await new Promise((res) => setTimeout(res, intervalMs));
  }

  return null;
}

const READER_ID_STORAGE_KEY = 'vs_article_reader_id';
const memoryState = {
  readerId: null,
  readerIdPersisted: false,
  reactions: new Map(),
};

const uuidPattern = /^[\da-f]{8}(?:-[\da-f]{4}){3}-[\da-f]{12}$/i;

function getStorage(candidate, globalKey) {
  if (candidate !== undefined) {
    return candidate;
  }
  try {
    return globalThis[globalKey] || null;
  } catch {
    return null;
  }
}

function isAllowedReaction(value, allowedKeys) {
  return typeof value === 'string' && allowedKeys.includes(value);
}

function createUuid(cryptoProvider) {
  if (typeof cryptoProvider?.randomUUID === 'function') {
    const id = cryptoProvider.randomUUID();
    if (uuidPattern.test(id)) {
      return id.toLowerCase();
    }
  }

  const bytes = new Uint8Array(16);
  if (typeof cryptoProvider?.getRandomValues === 'function') {
    cryptoProvider.getRandomValues(bytes);
  } else {
    bytes.forEach((_, index) => {
      bytes[index] = Math.floor(Math.random() * 256);
    });
  }
  bytes[6] = (bytes[6] & 0x0f) | 0x40;
  bytes[8] = (bytes[8] & 0x3f) | 0x80;
  const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
  return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

export function getReaderIdentity(options = {}) {
  const localStorage = getStorage(options.localStorage, 'localStorage');
  const sessionStorage = getStorage(options.sessionStorage, 'sessionStorage');
  const storageCandidates = [localStorage, sessionStorage].filter(Boolean);
  const state = options.memory || memoryState;

  for (const storage of storageCandidates) {
    try {
      const savedId = storage.getItem(READER_ID_STORAGE_KEY);
      if (savedId && uuidPattern.test(savedId)) {
        state.readerId = savedId.toLowerCase();
        state.readerIdPersisted = true;
        return { id: state.readerId, persisted: true };
      }
      if (savedId) {
        storage.removeItem(READER_ID_STORAGE_KEY);
      }
    } catch {
      continue;
    }
  }

  if (state.readerId) {
    return { id: state.readerId, persisted: state.readerIdPersisted };
  }

  const cryptoProvider = options.cryptoProvider === undefined
    ? globalThis.crypto
    : options.cryptoProvider;
  state.readerId = createUuid(cryptoProvider);
  state.readerIdPersisted = false;
  for (const storage of storageCandidates) {
    try {
      storage.setItem(READER_ID_STORAGE_KEY, state.readerId);
      state.readerIdPersisted = true;
      break;
    } catch {
      continue;
    }
  }

  return { id: state.readerId, persisted: state.readerIdPersisted };
}

function reactionStorageKey(articleId) {
  return `vs_article_reaction_${articleId}`;
}

export function getStoredReaction(articleId, allowedKeys, options = {}) {
  const key = String(articleId);
  const localStorage = getStorage(options.localStorage, 'localStorage');
  const sessionStorage = getStorage(options.sessionStorage, 'sessionStorage');
  const storageCandidates = [localStorage, sessionStorage].filter(Boolean);
  const reactions = options.reactions || memoryState.reactions;
  if (reactions.has(key)) {
    return reactions.get(key);
  }

  for (const storage of storageCandidates) {
    try {
      const savedReaction = storage.getItem(reactionStorageKey(key));
      if (!savedReaction) {
        continue;
      }
      if (isAllowedReaction(savedReaction, allowedKeys)) {
        reactions.set(key, savedReaction);
        return savedReaction;
      }
      storage.removeItem(reactionStorageKey(key));
    } catch {
      continue;
    }
  }

  return '';
}

export function saveStoredReaction(articleId, reaction, allowedKeys, options = {}) {
  if (!isAllowedReaction(reaction, allowedKeys)) {
    throw new Error('Tepki türü geçersiz.');
  }

  const key = String(articleId);
  const localStorage = getStorage(options.localStorage, 'localStorage');
  const sessionStorage = getStorage(options.sessionStorage, 'sessionStorage');
  const storageCandidates = [localStorage, sessionStorage].filter(Boolean);
  const reactions = options.reactions || memoryState.reactions;
  reactions.set(key, reaction);

  for (const storage of storageCandidates) {
    try {
      storage.setItem(reactionStorageKey(key), reaction);
      return true;
    } catch {
      continue;
    }
  }

  return false;
}

export function buildReactionPayload(reaction, readerId, previousReaction, allowedKeys) {
  if (!isAllowedReaction(reaction, allowedKeys)) {
    throw new Error('Tepki türü geçersiz.');
  }
  if (typeof readerId !== 'string' || !uuidPattern.test(readerId)) {
    throw new Error('Tepki kimliği geçersiz.');
  }

  return {
    reaction,
    reader_id: readerId.toLowerCase(),
    previous_reaction: isAllowedReaction(previousReaction, allowedKeys) ? previousReaction : null,
  };
}

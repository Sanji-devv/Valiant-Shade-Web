import { globSync } from 'glob';
import path from 'path';

/** @param {string} query */
export function globSyncEntries(query) {
  return globSync(query).filter((file) => !path.basename(file).startsWith('_'));
}

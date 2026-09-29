import { MembersListPage } from './MembersListPage';

/**
 * All Members Directory Page
 * Displays all platform members (verified, unverified, pending, active, and blocked)
 * with multi-criteria filtering, status management, and direct Member Panel access.
 */
export function AllMembersPage() {
  return <MembersListPage defaultMode="all" />;
}

export default AllMembersPage;

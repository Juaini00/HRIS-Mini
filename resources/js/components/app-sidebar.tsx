import { Link, usePage } from '@inertiajs/react';
import {
  Bell,
  CalendarDays,
  Clock3,
  LayoutGrid,
  Users,
  WalletCards,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: dashboard(),
    icon: LayoutGrid,
  },
  { title: 'Karyawan', href: '/employees', icon: Users },
  { title: 'Cuti', href: '/leave', icon: CalendarDays },
  { title: 'Kehadiran', href: '/attendance', icon: Clock3 },
  { title: 'Payroll', href: '/payroll', icon: WalletCards },
  { title: 'Pengumuman', href: '/announcements', icon: Bell },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
  const { auth } = usePage().props;
  const visibleItems = mainNavItems.filter(
    (item) =>
      item.title !== 'Karyawan' ||
      ['super_admin', 'hr_admin'].includes(auth.user.role),
  );

  return (
    <Sidebar collapsible="icon" variant="inset">
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild>
              <Link href={dashboard()} prefetch>
                <AppLogo />
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>

      <SidebarContent>
        <NavMain items={visibleItems} />
      </SidebarContent>

      <SidebarFooter>
        <NavFooter items={footerNavItems} className="mt-auto" />
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  );
}

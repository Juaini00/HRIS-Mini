import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    BellRing,
    CalendarDays,
    Network,
    Clock3,
    LayoutGrid,
    Users,
    WalletCards,
    Settings,
    ShieldCheck,
    FileChartColumn,
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
    { title: 'Organisasi', href: '/organization', icon: Network },
    { title: 'Cuti', href: '/leave', icon: CalendarDays },
    { title: 'Kehadiran', href: '/attendance', icon: Clock3 },
    { title: 'Payroll', href: '/payroll', icon: WalletCards },
    { title: 'Pengumuman', href: '/announcements', icon: Bell },
    { title: 'Laporan', href: '/reports', icon: FileChartColumn },
    { title: 'Notifikasi', href: '/notifications', icon: BellRing },
    { title: 'Pengaturan', href: '/company-settings', icon: Settings },
    { title: 'Audit Log', href: '/audit-logs', icon: ShieldCheck },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { auth } = usePage().props;
    const visibleItems = mainNavItems
        .filter(
            (item) =>
                !['Karyawan', 'Organisasi', 'Laporan'].includes(item.title) ||
                ['super_admin', 'hr_admin'].includes(auth.user.role),
        )
        .filter(
            (item) =>
                !['Pengaturan', 'Audit Log'].includes(item.title) ||
                auth.user.role === 'super_admin',
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

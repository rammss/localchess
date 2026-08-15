<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    FolderGit2,
    LayoutGrid,
    Swords,
    Trophy,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import TeamSwitcher from '@/components/TeamSwitcher.vue';
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
import * as competitionRoutes from '@/routes/competitions';
import * as playerRoutes from '@/routes/players';
import * as rankingRoutes from '@/routes/rankings';
import * as tournamentRoutes from '@/routes/tournaments';
import type { NavItem } from '@/types';

const page = usePage();

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl.value,
            icon: LayoutGrid,
        },
    ];

    if (page.props.currentTeam) {
        const slug = page.props.currentTeam.slug;

        items.push(
            {
                title: 'Jugadores',
                href: playerRoutes.index.url(slug),
                icon: Users,
            },
            {
                title: 'Torneos',
                href: tournamentRoutes.index.url(slug),
                icon: Trophy,
            },
            {
                title: 'Competencias',
                href: competitionRoutes.index.url(slug),
                icon: Swords,
            },
            {
                title: 'Rankings',
                href: rankingRoutes.index.url(slug),
                icon: BarChart3,
            },
        );
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarMenu>
                <SidebarMenuItem>
                    <TeamSwitcher />
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

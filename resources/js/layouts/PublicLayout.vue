<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { login } from '@/routes';
import * as competitionRoutes from '@/routes/public/competitions';
import * as playerRoutes from '@/routes/public/players';
import * as rankingRoutes from '@/routes/public/rankings';
import * as tournamentRoutes from '@/routes/public/tournaments';
import type { PublicTeam } from '@/types';

const page = usePage<{ team: PublicTeam }>();

const navItems = [
    {
        title: 'Rankings',
        route: () => rankingRoutes.index.url(page.props.team.slug),
    },
    {
        title: 'Jugadores',
        route: () => playerRoutes.index.url(page.props.team.slug),
    },
    {
        title: 'Torneos',
        route: () => tournamentRoutes.index.url(page.props.team.slug),
    },
    {
        title: 'Competencias',
        route: () => competitionRoutes.index.url(page.props.team.slug),
    },
];
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b">
            <div
                class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
                    >
                        <AppLogoIcon
                            class="size-5 fill-current text-white dark:text-black"
                        />
                    </div>
                    <div>
                        <p class="text-sm leading-tight font-semibold">
                            {{ page.props.team.name }}
                        </p>
                        <p
                            v-if="page.props.team.acronym"
                            class="text-xs text-muted-foreground"
                        >
                            {{ page.props.team.acronym }}
                        </p>
                    </div>
                </div>
                <nav class="flex items-center gap-4 text-sm">
                    <Link
                        v-for="item in navItems"
                        :key="item.title"
                        :href="item.route()"
                        class="hover:underline"
                    >
                        {{ item.title }}
                    </Link>
                    <Link
                        :href="login()"
                        class="text-muted-foreground hover:underline"
                        >Ingresar</Link
                    >
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            <slot />
        </main>
    </div>
</template>

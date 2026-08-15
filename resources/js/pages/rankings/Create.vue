<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as rankingRoutes from '@/routes/rankings';
import type { Team } from '@/types';

const page = usePage<{ currentTeam: Team }>();

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Rankings',
                href: rankingRoutes.index(layoutProps.currentTeam!.slug),
            },
            {
                title: 'Nuevo',
                href: rankingRoutes.create(layoutProps.currentTeam!.slug),
            },
        ],
    }),
});
</script>

<template>
    <Head title="Nuevo ranking" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <Heading
            variant="small"
            title="Nuevo ranking"
            description="Un sistema de ELO independiente, con sus propias reglas de K-factor"
        />

        <Form
            v-bind="rankingRoutes.store.form(page.props.currentTeam.slug)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="name">Nombre</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        placeholder="Ranking Ajedrecístico Marplatense"
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="acronym">Sigla</Label>
                    <Input
                        id="acronym"
                        name="acronym"
                        required
                        placeholder="RAM"
                    />
                    <InputError :message="errors.acronym" />
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="is_default"
                        name="is_default"
                        :default-value="true"
                    />
                    <Label for="is_default"
                        >Ranking por defecto de la federación</Label
                    >
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox id="state" name="state" :default-value="true" />
                    <Label for="state">Activo</Label>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="grid gap-2">
                    <Label for="ranking_base">ELO base de entrada</Label>
                    <Input
                        id="ranking_base"
                        name="ranking_base"
                        type="number"
                        default-value="1400"
                        required
                    />
                    <InputError :message="errors.ranking_base" />
                </div>
                <div class="grid gap-2">
                    <Label for="ranking_restart">Umbral de reinicio</Label>
                    <Input
                        id="ranking_restart"
                        name="ranking_restart"
                        type="number"
                        default-value="1000"
                        required
                    />
                    <InputError :message="errors.ranking_restart" />
                </div>
                <div class="grid gap-2">
                    <Label for="ranking_max_entry">Tope de entrada</Label>
                    <Input
                        id="ranking_max_entry"
                        name="ranking_max_entry"
                        type="number"
                        default-value="2200"
                        required
                    />
                    <InputError :message="errors.ranking_max_entry" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="grid gap-2">
                    <Label for="k_base">K base</Label>
                    <Input
                        id="k_base"
                        name="k_base"
                        type="number"
                        default-value="40"
                        required
                    />
                    <InputError :message="errors.k_base" />
                </div>
                <div class="grid gap-2">
                    <Label for="k_rapid">K rápidas</Label>
                    <Input
                        id="k_rapid"
                        name="k_rapid"
                        type="number"
                        step="0.1"
                        default-value="0.8"
                        required
                    />
                    <InputError :message="errors.k_rapid" />
                </div>
                <div class="grid gap-2">
                    <Label for="k_blitz">K blitz</Label>
                    <Input
                        id="k_blitz"
                        name="k_blitz"
                        type="number"
                        step="0.1"
                        default-value="0.6"
                        required
                    />
                    <InputError :message="errors.k_blitz" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="grid gap-2">
                    <Label for="valuation_games_standard"
                        >Partidas mínimas — clásica</Label
                    >
                    <Input
                        id="valuation_games_standard"
                        name="valuation_games_standard"
                        type="number"
                        default-value="5"
                        required
                    />
                    <InputError :message="errors.valuation_games_standard" />
                </div>
                <div class="grid gap-2">
                    <Label for="valuation_games_rapid"
                        >Partidas mínimas — rápidas</Label
                    >
                    <Input
                        id="valuation_games_rapid"
                        name="valuation_games_rapid"
                        type="number"
                        default-value="5"
                        required
                    />
                    <InputError :message="errors.valuation_games_rapid" />
                </div>
                <div class="grid gap-2">
                    <Label for="valuation_games_blitz"
                        >Partidas mínimas — blitz</Label
                    >
                    <Input
                        id="valuation_games_blitz"
                        name="valuation_games_blitz"
                        type="number"
                        default-value="5"
                        required
                    />
                    <InputError :message="errors.valuation_games_blitz" />
                </div>
            </div>

            <Button type="submit" :disabled="processing">Crear ranking</Button>
        </Form>
    </div>
</template>

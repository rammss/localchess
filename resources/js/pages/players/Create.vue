<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as playerRoutes from '@/routes/players';
import type { Team } from '@/types';

const page = usePage<{ currentTeam: Team }>();

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Jugadores',
                href: playerRoutes.index(layoutProps.currentTeam!.slug),
            },
            {
                title: 'Nuevo',
                href: playerRoutes.create(layoutProps.currentTeam!.slug),
            },
        ],
    }),
});

const selectClass =
    'flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30 md:text-sm';
</script>

<template>
    <Head title="Nuevo jugador" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <Heading
            variant="small"
            title="Nuevo jugador"
            description="Alta manual de un jugador en el directorio"
        />

        <Form
            v-bind="playerRoutes.store.form(page.props.currentTeam.slug)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="name">Nombre</Label>
                    <Input id="name" name="name" required placeholder="Ana" />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="last_name">Apellido</Label>
                    <Input
                        id="last_name"
                        name="last_name"
                        placeholder="García"
                    />
                    <InputError :message="errors.last_name" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="grid gap-2">
                    <Label for="gender">Género</Label>
                    <select id="gender" name="gender" :class="selectClass">
                        <option value="">Sin especificar</option>
                        <option value="1">Masculino</option>
                        <option value="0">Femenino</option>
                    </select>
                    <InputError :message="errors.gender" />
                </div>
                <div class="grid gap-2">
                    <Label for="title">Título FIDE</Label>
                    <select id="title" name="title" :class="selectClass">
                        <option value="">Sin título</option>
                        <option
                            v-for="t in [
                                'GM',
                                'WGM',
                                'IM',
                                'WIM',
                                'FM',
                                'WFM',
                                'CM',
                                'WCM',
                            ]"
                            :key="t"
                            :value="t"
                        >
                            {{ t }}
                        </option>
                    </select>
                    <InputError :message="errors.title" />
                </div>
                <div class="grid gap-2">
                    <Label for="birth_date">Fecha de nacimiento</Label>
                    <Input id="birth_date" name="birth_date" type="date" />
                    <InputError :message="errors.birth_date" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="club_name">Club</Label>
                    <Input id="club_name" name="club_name" />
                    <InputError :message="errors.club_name" />
                </div>
                <div class="grid gap-2">
                    <Label for="country">País</Label>
                    <Input id="country" name="country" placeholder="ARG" />
                    <InputError :message="errors.country" />
                </div>
            </div>

            <Button type="submit" :disabled="processing">Crear jugador</Button>
        </Form>
    </div>
</template>

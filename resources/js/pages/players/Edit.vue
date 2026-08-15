<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as playerRoutes from '@/routes/players';
import type { Player, Team } from '@/types';

const props = defineProps<{
    player: Player;
}>();

const page = usePage<{ currentTeam: Team }>();
const deleting = ref(false);

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Jugadores',
                href: playerRoutes.index(layoutProps.currentTeam!.slug),
            },
        ],
    }),
});

const selectClass =
    'flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30 md:text-sm';
</script>

<template>
    <Head :title="`${props.player.last_name}, ${props.player.name}`" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                :title="`${props.player.last_name}, ${props.player.name}`"
                description="Editar jugador"
            />
            <Button variant="destructive" size="sm" @click="deleting = true">
                <Trash2 class="h-4 w-4" /> Eliminar
            </Button>
        </div>

        <Form
            v-bind="
                playerRoutes.update.form([
                    page.props.currentTeam.slug,
                    props.player.id,
                ])
            "
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
                        :default-value="props.player.name"
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="last_name">Apellido</Label>
                    <Input
                        id="last_name"
                        name="last_name"
                        :default-value="props.player.last_name ?? ''"
                    />
                    <InputError :message="errors.last_name" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="grid gap-2">
                    <Label for="gender">Género</Label>
                    <select id="gender" name="gender" :class="selectClass">
                        <option
                            value=""
                            :selected="props.player.gender === null"
                        >
                            Sin especificar
                        </option>
                        <option
                            value="1"
                            :selected="props.player.gender === true"
                        >
                            Masculino
                        </option>
                        <option
                            value="0"
                            :selected="props.player.gender === false"
                        >
                            Femenino
                        </option>
                    </select>
                    <InputError :message="errors.gender" />
                </div>
                <div class="grid gap-2">
                    <Label for="title">Título FIDE</Label>
                    <select id="title" name="title" :class="selectClass">
                        <option value="" :selected="!props.player.title">
                            Sin título
                        </option>
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
                            :selected="props.player.title === t"
                        >
                            {{ t }}
                        </option>
                    </select>
                    <InputError :message="errors.title" />
                </div>
                <div class="grid gap-2">
                    <Label for="birth_date">Fecha de nacimiento</Label>
                    <Input
                        id="birth_date"
                        name="birth_date"
                        type="date"
                        :default-value="props.player.birth_date ?? ''"
                    />
                    <InputError :message="errors.birth_date" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="club_name">Club</Label>
                    <Input
                        id="club_name"
                        name="club_name"
                        :default-value="props.player.club_name ?? ''"
                    />
                    <InputError :message="errors.club_name" />
                </div>
                <div class="grid gap-2">
                    <Label for="country">País</Label>
                    <Input
                        id="country"
                        name="country"
                        :default-value="props.player.country ?? ''"
                    />
                    <InputError :message="errors.country" />
                </div>
            </div>

            <Button type="submit" :disabled="processing"
                >Guardar cambios</Button
            >
        </Form>
    </div>

    <ConfirmDeleteDialog
        :open="deleting"
        title="¿Eliminar este jugador?"
        :description="`Se eliminará a '${props.player.last_name}, ${props.player.name}'. Esta acción no se puede deshacer.`"
        :form="
            playerRoutes.destroy.form([
                page.props.currentTeam.slug,
                props.player.id,
            ])
        "
        @update:open="(open) => (deleting = open)"
    />
</template>

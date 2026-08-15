<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { RouteFormDefinition } from '@/wayfinder';

defineProps<{
    open: boolean;
    title: string;
    description: string;
    form: RouteFormDefinition<'post' | 'delete' | 'put' | 'patch'>;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent>
            <Form
                v-bind="form"
                v-slot="{ processing }"
                @success="emit('update:open', false)"
            >
                <DialogHeader>
                    <DialogTitle>{{ title }}</DialogTitle>
                    <DialogDescription>{{ description }}</DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2 pt-4">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancelar</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        type="submit"
                        :disabled="processing"
                    >
                        Eliminar
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>

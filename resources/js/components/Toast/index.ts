import { cva, type VariantProps } from 'class-variance-authority';

export { default as Toast } from './Toast.vue';

export const toastVariants = cva('text-sm p-4 rounded-md border w-max min-w-xs max-w-sm', {
    variants: {
        variant: {
            success: 'bg-green-50 border-green-100',
            warning: 'bg-yellow-50 border-yellow-100',
            error: 'bg-red-50 border-red-100',
            info: 'bg-blue-50 border-blue-100',
        },
    },
    defaultVariants: {
        variant: 'info',
    },
});

export const toastContentVariants = cva('flex items-center gap-2.5 font-medium', {
    variants: {
        variant: {
            success: 'text-green-900',
            warning: 'text-yellow-900',
            error: 'text-red-900',
            info: 'text-blue-900',
        },
    },
    defaultVariants: {
        variant: 'info',
    },
});

export type ToastVariants = VariantProps<typeof toastVariants>;

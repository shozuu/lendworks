import { cva } from 'class-variance-authority';

export { default as Alert } from './Alert.vue';
export { default as AlertDescription } from './AlertDescription.vue';
export { default as AlertTitle } from './AlertTitle.vue';

export const alertVariants = cva(
  'w-full flex items-start rounded-lg border p-4 space-x-3',
  {
    variants: {
      variant: {
        default: 'bg-background text-foreground',
        destructive:
          'border-destructive/50 text-destructive dark:border-destructive [&>svg]:text-destructive',
        success:
          'border-green-500 text-green-700 dark:bg-green-700/10 dark:border-green-700 dark:text-green-200 [&>svg]:text-green-500',
        warning:
          'border-yellow-500 text-yellow-700 dark:bg-yellow-700/10 dark:border-yellow-700 dark:text-yellow-200 [&>svg]:text-yellow-500',
        info:
          'border-blue-500 text-blue-700 dark:bg-blue-700/10 dark:border-blue-700 dark:text-blue-200 [&>svg]:text-blue-500',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  }
);


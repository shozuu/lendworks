import { RadioGroup as RadioGroupPrimitive } from '@headlessui/vue'

export const RadioGroup = RadioGroupPrimitive
export const RadioGroupLabel = RadioGroupPrimitive.Label
export const RadioGroupItem = (props) => {
  return (
    <RadioGroupPrimitive.Option
      {...props}
      class={({ active, checked }) => `
        ${checked ? 'bg-primary border-transparent text-primary-foreground' : 'bg-background'}
        relative flex cursor-pointer rounded-lg px-5 py-4 border-2 focus:outline-none
      `}
    >
      {({ active, checked }) => (
        <>
          <div class="flex w-full items-center justify-between">
            <div class="flex items-center">
              <div class="text-sm">
                <RadioGroupPrimitive.Label class="font-medium">
                  {props.children}
                </RadioGroupPrimitive.Label>
              </div>
            </div>
            {checked && (
              <div class="text-white">
                <CheckIcon class="h-6 w-6" />
              </div>
            )}
          </div>
        </>
      )}
    </RadioGroupPrimitive.Option>
  )
}

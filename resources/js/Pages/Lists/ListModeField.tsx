import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';

export type ListMode = 'default' | 'listening';

const MODE_OPTIONS: { value: ListMode; label: string; description: string }[] = [
    {
        value: 'default',
        label: 'Default',
        description: 'Open, move, and remove shown as inline buttons.',
    },
    {
        value: 'listening',
        label: 'Listening',
        description: 'A prominent "I listened to this" action; move and remove move into a menu.',
    },
];

export default function ListModeField({
    value,
    onChange,
}: {
    value: ListMode;
    onChange: (value: ListMode) => void;
}) {
    return (
        <div className="space-y-2">
            <Label>List mode</Label>
            <RadioGroup
                value={value}
                onValueChange={(next) => onChange(next as ListMode)}
            >
                {MODE_OPTIONS.map((option) => (
                    <Label
                        key={option.value}
                        className="flex cursor-pointer items-start gap-3 rounded-md border p-3 font-normal has-[[data-checked]]:border-primary"
                    >
                        <RadioGroupItem value={option.value} className="mt-0.5" />
                        <span className="space-y-0.5">
                            <span className="block text-sm font-medium">
                                {option.label}
                            </span>
                            <span className="block text-sm text-muted-foreground">
                                {option.description}
                            </span>
                        </span>
                    </Label>
                ))}
            </RadioGroup>
        </div>
    );
}

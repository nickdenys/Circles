import { router, useForm, usePage } from '@inertiajs/react';
import { Copy, ExternalLink, Trash2 } from 'lucide-react';
import { FormEvent, useCallback, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Token {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string;
}

interface SettingsProps {
    tokens: Token[];
    flash: {
        token: string | null;
    };
    [key: string]: unknown;
}

export default function Index() {
    const { tokens, flash } = usePage<SettingsProps>().props;
    const [copied, setCopied] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();

        post('/settings/tokens', {
            onSuccess: () => {
                reset();
            },
        });
    }

    const handleCopy = useCallback(
        (text: string) => {
            navigator.clipboard.writeText(text);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        },
        [],
    );

    const handleDelete = useCallback((tokenId: number) => {
        router.delete(`/settings/tokens/${tokenId}`);
    }, []);

    return (
        <div>
            <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                Settings
            </h1>

            <div className="mt-8 space-y-8">
                {flash.token && (
                    <Card className="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/50">
                        <CardContent className="pt-6">
                            <p className="mb-2 font-medium text-green-900 dark:text-green-100">
                                Copy this token now. It won't be shown again.
                            </p>
                            <div className="flex items-center gap-2">
                                <code className="flex-1 rounded bg-white px-3 py-2 text-sm break-all dark:bg-zinc-900">
                                    {flash.token}
                                </code>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handleCopy(flash.token!)}
                                >
                                    <Copy className="mr-1.5 h-4 w-4" />
                                    {copied ? 'Copied!' : 'Copy'}
                                </Button>
                            </div>
                            <div className="mt-4">
                                <p className="text-sm text-green-800 dark:text-green-200">
                                    Add this to your Claude Desktop config (<code className="rounded bg-white/60 px-1 py-0.5 dark:bg-zinc-800/60">~/Library/Application Support/Claude/claude_desktop_config.json</code>):
                                </p>
                                <div className="relative mt-2">
                                    <pre className="overflow-x-auto rounded bg-white p-3 pr-16 text-xs dark:bg-zinc-900">
{`{
  "mcpServers": {
    "hoopify": {
      "command": "npx",
      "args": [
        "-y",
        "mcp-remote",
        "${window.location.origin}/mcp",
        "--header",
        "Authorization: Bearer ${flash.token}"
      ],
      "env": {
        "NODE_TLS_REJECT_UNAUTHORIZED": "0"
      }
    }
  }
}`}
                                    </pre>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="absolute top-2 right-2"
                                        onClick={() => handleCopy(JSON.stringify({
                                            mcpServers: {
                                                hoopify: {
                                                    command: 'npx',
                                                    args: [
                                                        '-y',
                                                        'mcp-remote',
                                                        `${window.location.origin}/mcp`,
                                                        '--header',
                                                        `Authorization: Bearer ${flash.token}`,
                                                    ],
                                                    env: {
                                                        NODE_TLS_REJECT_UNAUTHORIZED: '0',
                                                    },
                                                },
                                            },
                                        }, null, 2))}
                                    >
                                        <Copy className="mr-1.5 h-4 w-4" />
                                        {copied ? 'Copied!' : 'Copy'}
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Spotify Connection</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="mb-3 text-sm text-muted-foreground">
                            Reconnect your Spotify account to grant updated permissions (e.g. playlist creation).
                        </p>
                        <a href="/auth/spotify/reconnect">
                            <Button variant="outline">
                                <ExternalLink className="mr-1.5 h-4 w-4" />
                                Reconnect Spotify
                            </Button>
                        </a>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Create API Token</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="flex items-end gap-3">
                            <div className="flex-1 space-y-2">
                                <Label htmlFor="name">Token Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g. Claude Desktop"
                                    aria-invalid={!!errors.name}
                                />
                                {errors.name && (
                                    <p className="text-sm text-destructive">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                            <Button type="submit" disabled={processing}>
                                Create Token
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>API Tokens</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {tokens.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No API tokens yet. Create one to connect Claude Desktop.
                            </p>
                        ) : (
                            <div className="divide-y divide-zinc-200 dark:divide-zinc-800">
                                {tokens.map((token) => (
                                    <div
                                        key={token.id}
                                        className="flex items-center justify-between py-3 first:pt-0 last:pb-0"
                                    >
                                        <div>
                                            <p className="font-medium">{token.name}</p>
                                            <p className="mt-0.5 text-sm text-muted-foreground">
                                                Created {token.created_at}
                                                {token.last_used_at && (
                                                    <> &middot; Last used {token.last_used_at}</>
                                                )}
                                            </p>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => handleDelete(token.id)}
                                            className="text-muted-foreground hover:text-destructive"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

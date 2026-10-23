import { useEffect, useId, useRef, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    LoaderCircle,
    X,
} from 'lucide-react';
import { ApiError, errorText } from '@/shared/api/client';
import type { Page } from '@/shared/api/types';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
export const inputClass =
    'h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-100 disabled:opacity-50';
export const buttonClass =
    'inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-orange-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600 disabled:cursor-not-allowed disabled:opacity-50';
export const secondaryClass =
    'inline-flex min-h-9 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40';
export function Title({
    title,
    description,
    action,
}: {
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <header className="mb-7 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p className="mb-2 text-[10px] font-semibold tracking-widest text-orange-700 uppercase">
                    DeliveryHub
                </p>
                <h1 className="text-3xl font-bold tracking-tight text-slate-900">
                    {title}
                </h1>
                {description && (
                    <p className="mt-2 text-sm leading-6 text-slate-500">
                        {description}
                    </p>
                )}
            </div>
            {action}
        </header>
    );
}
export function Feedback({
    message,
    success = false,
}: {
    message: string;
    success?: boolean;
}) {
    return message ? (
        <div
            role={success ? 'status' : 'alert'}
            className={`mb-5 flex items-start gap-2 rounded-xl border p-4 text-sm leading-6 ${success ? 'border-emerald-100 bg-emerald-50 text-emerald-800' : 'border-red-100 bg-red-50 text-red-800'}`}
        >
            <AlertCircle
                className="mt-1 shrink-0"
                size={16}
                aria-hidden="true"
            />
            <div>
                {!success && (
                    <p className="mb-1 font-semibold">
                        Não foi possível continuar
                    </p>
                )}
                <p>{message}</p>
            </div>
        </div>
    ) : null;
}
export function LoadState({
    loading,
    error,
    retry,
}: {
    loading: boolean;
    error: string;
    retry: () => void;
}) {
    if (loading)
        return (
            <p
                role="status"
                className="flex items-center gap-2 py-12 text-sm text-slate-500"
            >
                <LoaderCircle className="animate-spin" size={18} />A carregar
                dados…
            </p>
        );
    return error ? (
        <div>
            <Feedback message={error} />
            <button className={secondaryClass} onClick={retry}>
                Tentar novamente
            </button>
        </div>
    ) : null;
}
export function Empty({
    text = 'Sem registos para apresentar.',
}: {
    text?: string;
}) {
    return (
        <p className="rounded-xl border border-dashed border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            {text}
        </p>
    );
}
export function Pagination({
    data,
    go,
    busy,
}: {
    data: Page<unknown> | null;
    go: (page: number) => void;
    busy?: boolean;
}) {
    if (!data) return null;
    return (
        <div className="mt-5 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
            <p>
                {data.meta.from ?? 0}–{data.meta.to ?? 0} de {data.meta.total} ·
                Página {data.meta.current_page} de {data.meta.last_page}
            </p>
            <div className="flex gap-2">
                <button
                    disabled={busy || !data.links.prev}
                    className={secondaryClass}
                    onClick={() => go(data.meta.current_page - 1)}
                >
                    <ArrowLeft size={14} />
                    Anterior
                </button>
                <button
                    disabled={busy || !data.links.next}
                    className={secondaryClass}
                    onClick={() => go(data.meta.current_page + 1)}
                >
                    Seguinte
                    <ArrowRight size={14} />
                </button>
            </div>
        </div>
    );
}
export function SearchBox({
    onSearch,
    placeholder = 'Pesquisar por nome…',
}: {
    onSearch: (search: string) => void;
    placeholder?: string;
}) {
    const [value, setValue] = useState('');
    const id = useId();
    useEffect(() => {
        const timeout = window.setTimeout(() => onSearch(value.trim()), 300);
        return () => window.clearTimeout(timeout);
    }, [value, onSearch]);
    return (
        <form
            noValidate
            role="search"
            className="mb-5 flex max-w-lg gap-2"
            onSubmit={(event) => {
                event.preventDefault();
                onSearch(value.trim());
            }}
        >
            <label htmlFor={id} className="sr-only">
                Pesquisar
            </label>
            <input
                id={id}
                type="search"
                value={value}
                onChange={(event) => setValue(event.target.value)}
                placeholder={placeholder}
                className={inputClass}
            />
            {value && (
                <button
                    type="button"
                    aria-label="Limpar pesquisa"
                    className={secondaryClass}
                    onClick={() => {
                        setValue('');
                        onSearch('');
                    }}
                >
                    <X size={15} />
                </button>
            )}
        </form>
    );
}
export interface Field {
    name: string;
    label: string;
    type?:
        | 'text'
        | 'email'
        | 'password'
        | 'tel'
        | 'number'
        | 'date'
        | 'textarea'
        | 'select';
    required?: boolean;
    options?: { value: string | number; label: string }[];
    hint?: string;
    defaultValue?: string | number;
}
export function Fields({
    fields,
    initial = {},
    errors = {},
}: {
    fields: Field[];
    initial?: Record<string, string | number>;
    errors?: Record<string, string>;
}) {
    return (
        <>
            {fields.map((field) => {
                const props = {
                    id: `field-${field.name}`,
                    name: field.name,
                    'aria-required': field.required,
                    defaultValue:
                        initial[field.name] ?? field.defaultValue ?? '',
                    'aria-invalid': !!errors[field.name],
                    'aria-describedby': errors[field.name]
                        ? `error-${field.name}`
                        : field.hint
                          ? `hint-${field.name}`
                          : undefined,
                    className: inputClass,
                };
                return (
                    <div key={field.name} className="grid gap-2">
                        <label
                            htmlFor={props.id}
                            className="text-sm font-medium text-slate-700"
                        >
                            {field.label}
                            {field.required && (
                                <span className="ml-1 text-slate-400">*</span>
                            )}
                        </label>
                        {field.type === 'select' ? (
                            <select {...props}>
                                <option value="">Selecione…</option>
                                {field.options?.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        ) : field.type === 'textarea' ? (
                            <textarea
                                {...props}
                                rows={3}
                                className={`${inputClass} h-auto py-3`}
                            />
                        ) : (
                            <input
                                {...props}
                                type={field.type ?? 'text'}
                                autoComplete={
                                    field.type === 'password'
                                        ? 'new-password'
                                        : field.type === 'email'
                                          ? 'email'
                                          : 'off'
                                }
                            />
                        )}
                        {field.hint && (
                            <p
                                id={`hint-${field.name}`}
                                className="text-xs leading-5 text-slate-400"
                            >
                                {field.hint}
                            </p>
                        )}
                        {errors[field.name] && (
                            <p
                                id={`error-${field.name}`}
                                className="text-xs text-red-700"
                            >
                                {errors[field.name]}
                            </p>
                        )}
                    </div>
                );
            })}
        </>
    );
}
export function ApiForm({
    fields,
    initial = {},
    submit,
    done,
    label = 'Guardar',
}: {
    fields: Field[];
    initial?: Record<string, string | number>;
    submit: (data: Record<string, unknown>) => Promise<unknown>;
    done: (result: unknown) => void;
    label?: string;
}) {
    const [busy, setBusy] = useState(false);
    const lock = useRef(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [message, setMessage] = useState('');
    async function send(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (lock.current) return;
        const entries = new FormData(event.currentTarget);
        const data: Record<string, unknown> = {};
        for (const field of fields) {
            const value = entries.get(field.name);
            if (typeof value === 'string') data[field.name] = value;
        }
        lock.current = true;
        setBusy(true);
        setErrors({});
        setMessage('');
        try {
            done(await submit(data));
        } catch (error) {
            setErrors(error instanceof ApiError ? error.fields : {});
            setMessage(
                errorText(error) +
                    (error instanceof ApiError && error.requestId
                        ? ` Referência: ${error.requestId}`
                        : ''),
            );
        } finally {
            lock.current = false;
            setBusy(false);
        }
    }
    return (
        <form
            noValidate
            onSubmit={(event) => void send(event)}
            aria-busy={busy}
            className="space-y-5"
        >
            <Feedback message={message} />
            <fieldset disabled={busy} className="grid gap-5">
                <Fields fields={fields} initial={initial} errors={errors} />
            </fieldset>
            <button
                disabled={busy}
                className={`${buttonClass} w-full`}
                type="submit"
            >
                {busy && <LoaderCircle className="animate-spin" size={16} />}
                {busy ? 'A processar…' : label}
            </button>
        </form>
    );
}
export function EditDialog({
    title,
    children,
    close,
    description = 'Os dados serão guardados no servidor.',
}: {
    title: string;
    children: ReactNode;
    close: () => void;
    description?: string;
}) {
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open) close();
            }}
        >
            <DialogContent className="max-h-[90svh] overflow-y-auto bg-white text-slate-900 sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                {children}
            </DialogContent>
        </Dialog>
    );
}
export function ConfirmAction({
    title,
    action,
    done,
    close,
}: {
    title: string;
    action: () => Promise<unknown>;
    done: () => void;
    close: () => void;
}) {
    return (
        <EditDialog
            title={title}
            close={close}
            description="Confirme esta alteração. O servidor irá validar as permissões e as regras da operação."
        >
            <ApiForm
                fields={[]}
                label="Confirmar alteração"
                submit={action}
                done={done}
            />
            <button className={`${secondaryClass} w-full`} onClick={close}>
                Voltar
            </button>
        </EditDialog>
    );
}

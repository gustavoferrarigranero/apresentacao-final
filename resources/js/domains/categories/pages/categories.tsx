import { useState } from 'react';
import { Plus } from 'lucide-react';
import { categoryApi, categoryEndpoints } from '../services';
import { useList } from '@/shared/hooks/use-api';
import { useSession } from '@/domains/users/context/session';
import { isTeam } from '@/domains/users/permissions';
import type { Category } from '@/domains/categories/types';
import {
    ApiForm,
    ConfirmAction,
    EditDialog,
    Empty,
    Feedback,
    LoadState,
    Pagination,
    SearchBox,
    Title,
    buttonClass,
    secondaryClass,
} from '@/shared/components/api-ui';

import { stateField, ActiveBadge } from '@/shared/components/catalog-state';
export function CategoriesManagement() {
    const { user } = useSession();
    const [search, setSearch] = useState('');
    const list = useList<Category>(
        isTeam(user) ? categoryEndpoints.list : null,
        0,
        12,
        search,
    );
    const [edit, setEdit] = useState<Category | 'new' | null>(null);
    const [remove, setRemove] = useState<Category | null>(null);
    const [notice, setNotice] = useState('');
    if (!isTeam(user))
        return (
            <>
                <Title title="Categorias" />
                <Empty text="Esta área é exclusiva da equipa do restaurante." />
            </>
        );
    return (
        <>
            <Title
                title="Categorias"
                description="Organize o catálogo do seu restaurante."
                action={
                    <button
                        className={buttonClass}
                        onClick={() => setEdit('new')}
                    >
                        <Plus size={16} />
                        Nova categoria
                    </button>
                }
            />
            <Feedback message={notice} success />
            <SearchBox onSearch={setSearch} />
            <LoadState {...list} retry={list.retry} />
            {list.value && !list.loading && (
                <>
                    <section className="rounded-2xl border border-slate-200 bg-white p-5">
                        <ul className="divide-y divide-slate-100">
                            {list.value.data.map((category) => (
                                <li
                                    key={category.id}
                                    className="flex flex-wrap items-center justify-between gap-3 py-4"
                                >
                                    <div>
                                        <h2 className="text-sm font-semibold">
                                            {category.name}
                                        </h2>
                                        <p className="mt-1 text-xs text-slate-400">
                                            Categoria #{category.id}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <ActiveBadge status={category.status} />
                                        <button
                                            className={secondaryClass}
                                            onClick={() => setEdit(category)}
                                        >
                                            Editar
                                        </button>
                                        {category.status === 'active' && (
                                            <button
                                                className={secondaryClass}
                                                onClick={() =>
                                                    setRemove(category)
                                                }
                                            >
                                                Desativar
                                            </button>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                        {!list.value.data.length && <Empty />}
                    </section>
                    <Pagination data={list.value} go={list.go} />
                </>
            )}
            {edit && (
                <EditDialog
                    title={
                        edit === 'new' ? 'Nova categoria' : 'Editar categoria'
                    }
                    close={() => setEdit(null)}
                >
                    <ApiForm
                        fields={[
                            {
                                name: 'name',
                                label: 'Nome',
                                required: true,
                            },
                            stateField,
                        ]}
                        initial={
                            edit === 'new'
                                ? {}
                                : { name: edit.name, status: edit.status }
                        }
                        submit={(data) =>
                            categoryApi.save(
                                edit === 'new' ? null : edit.id,
                                data,
                            )
                        }
                        done={() => {
                            setEdit(null);
                            setNotice('Categoria guardada no servidor.');
                            list.refresh();
                        }}
                    />
                </EditDialog>
            )}
            {remove && (
                <ConfirmAction
                    title={`Desativar ${remove.name}?`}
                    action={() => categoryApi.deactivate(remove.id)}
                    close={() => setRemove(null)}
                    done={() => {
                        setRemove(null);
                        list.refresh();
                        setNotice('Categoria desativada.');
                    }}
                />
            )}
        </>
    );
}

import { categoryApi } from '@/domains/categories/services';
import { useEffect, useState } from 'react';
import { Plus } from 'lucide-react';
import { productApi, productEndpoints } from '../services';
import { useList } from '@/shared/hooks/use-api';
import { useSession } from '@/domains/users/context/session';
import { decimalToCents, money } from '@/shared/utils/money';
import { isTeam } from '@/domains/users/permissions';
import type { Category } from '@/domains/categories/types';
import type { Resource } from '@/shared/api/types';
import type { Product } from '@/domains/products/types';
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
import type { Field } from '@/shared/components/api-ui';

import { stateField } from '@/shared/components/catalog-state';
export function ProductsManagement() {
    const { user } = useSession();
    const [search, setSearch] = useState('');
    const list = useList<Product>(
        isTeam(user) ? productEndpoints.catalog(user!.restaurant_id!) : null,
        0,
        12,
        search,
    );
    const [categories, setCategories] = useState<Category[]>([]);
    const [categoryError, setCategoryError] = useState('');
    const [categoryLoading, setCategoryLoading] = useState(true);
    const [categoryRevision, setCategoryRevision] = useState(0);
    const [edit, setEdit] = useState<Product | 'new' | null>(null);
    const [remove, setRemove] = useState<Product | null>(null);
    const [notice, setNotice] = useState('');
    const [lastUpdated, setLastUpdated] = useState<Product | null>(null);
    useEffect(() => {
        if (!isTeam(user)) return;
        const controller = new AbortController();
        setCategoryLoading(true);
        setCategoryError('');
        const load = async () => {
            const all = await categoryApi.options(controller.signal);
            if (!controller.signal.aborted) setCategories(all);
        };
        void load()
            .catch(() => {
                if (!controller.signal.aborted)
                    setCategoryError(
                        'Não foi possível carregar as categorias.',
                    );
            })
            .finally(() => {
                if (!controller.signal.aborted) setCategoryLoading(false);
            });
        return () => controller.abort();
    }, [user, categoryRevision]);
    if (!isTeam(user))
        return (
            <>
                <Title title="Produtos" />
                <Empty text="Esta área é exclusiva da equipa do restaurante." />
            </>
        );
    const fields: Field[] = [
        {
            name: 'name',
            label: 'Nome',
            required: true,
        },
        { name: 'type', label: 'Tipo', required: true },
        {
            name: 'price',
            label: 'Preço (AOA)',
            type: 'number',
            required: true,
        },
        {
            name: 'quantity',
            label: 'Quantidade em stock',
            type: 'number',
            required: true,
        },
        {
            name: 'category_id',
            label: 'Categoria',
            type: 'select',
            required: true,
            options: categories.map((category) => ({
                value: category.id,
                label: `${category.name}${category.status === 'inactive' ? ' (inativa)' : ''}`,
            })),
        },
        stateField,
    ];
    return (
        <>
            <Title
                title="Produtos"
                description="Catálogo publicado, preços e stock do seu restaurante."
                action={
                    <button
                        className={buttonClass}
                        disabled={
                            categoryLoading ||
                            !categories.length ||
                            !!categoryError
                        }
                        onClick={() => setEdit('new')}
                    >
                        <Plus size={16} />
                        Novo produto
                    </button>
                }
            />
            <Feedback message={notice} success />
            {categoryError && (
                <div className="mb-5">
                    <Feedback message={categoryError} />
                    <button
                        className={secondaryClass}
                        onClick={() =>
                            setCategoryRevision((value) => value + 1)
                        }
                    >
                        Tentar carregar categorias
                    </button>
                </div>
            )}
            {!categoryLoading && !categoryError && !categories.length && (
                <p className="mb-5 text-sm text-slate-500">
                    Crie uma{' '}
                    <a href="#categories" className="text-orange-700 underline">
                        categoria
                    </a>{' '}
                    antes de adicionar produtos.
                </p>
            )}
            <p className="mb-5 text-xs leading-6 text-slate-400">
                A listagem da API mostra apenas produtos ativos. Ao desativar, o
                produto deixa de aparecer no catálogo público.
            </p>
            {lastUpdated?.status === 'inactive' && (
                <div className="mb-5 flex flex-wrap items-center gap-3 rounded-lg border bg-white p-4 text-xs">
                    <span>
                        Último produto atualizado: {lastUpdated.name} (inativo)
                    </span>
                    <button
                        className={secondaryClass}
                        onClick={() => setEdit(lastUpdated)}
                    >
                        Editar novamente
                    </button>
                </div>
            )}
            <SearchBox onSearch={setSearch} />
            <LoadState {...list} retry={list.retry} />
            {list.value && !list.loading && (
                <>
                    <section className="rounded-2xl border border-slate-200 bg-white p-5">
                        <ul className="divide-y divide-slate-100">
                            {list.value.data.map((product) => (
                                <li
                                    key={product.id}
                                    className="flex flex-wrap items-center justify-between gap-3 py-4"
                                >
                                    <div>
                                        <h2 className="text-sm font-semibold">
                                            {product.name}
                                        </h2>
                                        <p className="mt-2 text-xs text-slate-500">
                                            {product.category?.name ??
                                                product.type}{' '}
                                            ·{' '}
                                            {money(
                                                decimalToCents(product.price),
                                            )}{' '}
                                            · Stock: {product.quantity}
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <button
                                            className={secondaryClass}
                                            disabled={
                                                categoryLoading ||
                                                !!categoryError
                                            }
                                            onClick={() => setEdit(product)}
                                        >
                                            Editar
                                        </button>
                                        <button
                                            className={secondaryClass}
                                            onClick={() => setRemove(product)}
                                        >
                                            Desativar
                                        </button>
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
                    title={edit === 'new' ? 'Novo produto' : 'Editar produto'}
                    close={() => setEdit(null)}
                >
                    <ApiForm
                        fields={fields}
                        initial={
                            edit === 'new'
                                ? {}
                                : {
                                      name: edit.name,
                                      type: edit.type,
                                      price: edit.price,
                                      quantity: edit.quantity,
                                      category_id: edit.category_id,
                                      status: edit.status,
                                  }
                        }
                        submit={(data) =>
                            productApi.save(
                                edit === 'new' ? null : edit.id,
                                data,
                            )
                        }
                        done={(result) => {
                            setLastUpdated((result as Resource<Product>).data);
                            setEdit(null);
                            setNotice('Produto guardado no servidor.');
                            list.refresh();
                        }}
                    />
                </EditDialog>
            )}
            {remove && (
                <ConfirmAction
                    title={`Desativar ${remove.name}?`}
                    action={() => productApi.deactivate(remove.id)}
                    close={() => setRemove(null)}
                    done={() => {
                        setLastUpdated({ ...remove, status: 'inactive' });
                        setRemove(null);
                        list.refresh();
                        setNotice('Produto desativado.');
                    }}
                />
            )}
        </>
    );
}

import { useState } from 'react';
import { Plus } from 'lucide-react';
import { restaurantApi, restaurantEndpoints } from '../services';
import { useList } from '@/shared/hooks/use-api';
import { useSession } from '@/domains/users/context/session';
import { canManageRestaurant } from '@/domains/restaurants/permissions';
import type { Restaurant } from '@/domains/restaurants/types';
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

import { stateField, ActiveBadge } from '@/shared/components/catalog-state';
export function RestaurantsManagement() {
    const { user } = useSession();
    const [search, setSearch] = useState('');
    const list = useList<Restaurant>(
        restaurantEndpoints.management(user),
        0,
        12,
        search,
    );
    const [edit, setEdit] = useState<Restaurant | 'new' | null>(null);
    const [remove, setRemove] = useState<Restaurant | null>(null);
    const [notice, setNotice] = useState('');
    const allowed =
        user?.role === 'super_admin' || user?.role === 'restaurant_admin';
    const fields: Field[] = [
        {
            name: 'name',
            label: 'Nome',
            required: true,
            minLength: 2,
            maxLength: 255,
        },
        { name: 'address', label: 'Morada', required: true, maxLength: 255 },
        {
            name: 'phone_number',
            label: 'Contacto',
            type: 'tel',
            required: true,
            maxLength: 30,
        },
        { name: 'nif', label: 'NIF', required: true, maxLength: 20 },
        ...(edit === 'new'
            ? [
                  {
                      name: 'admin_name',
                      label: 'Nome do administrador',
                      required: true,
                      minLength: 2,
                      maxLength: 255,
                  },
                  {
                      name: 'admin_email',
                      label: 'Email do administrador',
                      type: 'email' as const,
                      required: true,
                      maxLength: 255,
                  },
                  {
                      name: 'admin_password',
                      label: 'Palavra-passe do administrador',
                      type: 'password' as const,
                      required: true,
                      minLength: 12,
                      hint: 'Pelo menos 12 caracteres.',
                  },
              ]
            : [stateField]),
    ];
    return (
        <>
            <Title
                title="Restaurantes"
                description="Dados e contactos dos restaurantes disponíveis na sua conta."
                action={
                    user?.role === 'super_admin' && (
                        <button
                            className={buttonClass}
                            onClick={() => setEdit('new')}
                        >
                            <Plus size={16} />
                            Novo restaurante
                        </button>
                    )
                }
            />
            {!allowed ? (
                <Empty text="A gestão de restaurantes está disponível aos administradores autorizados." />
            ) : (
                <>
                    <Feedback message={notice} success />
                    <SearchBox onSearch={setSearch} />
                    <LoadState {...list} retry={list.retry} />
                    {list.value && !list.loading && (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                {list.value.data.map((restaurant) => (
                                    <article
                                        key={restaurant.id}
                                        className="rounded-2xl border border-slate-200 bg-white p-6"
                                    >
                                        <div className="mb-4 flex justify-between">
                                            <h2 className="font-semibold">
                                                {restaurant.name}
                                            </h2>
                                            <ActiveBadge
                                                status={restaurant.status}
                                            />
                                        </div>
                                        <p className="text-xs leading-6 text-slate-500">
                                            {restaurant.address}
                                            <br />
                                            {restaurant.phone_number}
                                            <br />
                                            NIF {restaurant.nif}
                                        </p>
                                        {canManageRestaurant(
                                            user,
                                            restaurant,
                                        ) && (
                                            <div className="mt-5 flex flex-wrap gap-2">
                                                <button
                                                    className={secondaryClass}
                                                    onClick={() =>
                                                        setEdit(restaurant)
                                                    }
                                                >
                                                    Editar
                                                </button>
                                                {restaurant.status ===
                                                    'active' && (
                                                    <button
                                                        className={
                                                            secondaryClass
                                                        }
                                                        onClick={() =>
                                                            setRemove(
                                                                restaurant,
                                                            )
                                                        }
                                                    >
                                                        Desativar
                                                    </button>
                                                )}
                                            </div>
                                        )}
                                    </article>
                                ))}
                            </div>
                            {!list.value.data.length && <Empty />}
                            <Pagination data={list.value} go={list.go} />
                        </>
                    )}
                </>
            )}
            {edit && allowed && (
                <EditDialog
                    title={
                        edit === 'new'
                            ? 'Criar restaurante e administrador'
                            : 'Editar restaurante'
                    }
                    close={() => setEdit(null)}
                >
                    <ApiForm
                        fields={fields}
                        initial={
                            edit === 'new'
                                ? {}
                                : {
                                      name: edit.name,
                                      address: edit.address,
                                      phone_number: edit.phone_number,
                                      nif: edit.nif,
                                      status: edit.status,
                                  }
                        }
                        submit={(data) =>
                            restaurantApi.save(
                                edit === 'new' ? null : edit.id,
                                data,
                            )
                        }
                        done={() => {
                            setNotice('Restaurante guardado no servidor.');
                            setEdit(null);
                            list.refresh();
                        }}
                    />
                </EditDialog>
            )}
            {remove && (
                <ConfirmAction
                    title={`Desativar ${remove.name}?`}
                    action={() => restaurantApi.deactivate(remove.id)}
                    close={() => setRemove(null)}
                    done={() => {
                        setRemove(null);
                        setNotice('Restaurante desativado.');
                        list.refresh();
                    }}
                />
            )}
        </>
    );
}

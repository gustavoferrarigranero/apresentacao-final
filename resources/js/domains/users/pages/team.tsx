import { useState } from 'react';

import { userApi } from '../services';

import { useSession } from '@/domains/users/context/session';

import type { Resource } from '@/shared/api/types';
import { ApiForm, Empty, Feedback, Title } from '@/shared/components/api-ui';

export function TeamManagement() {
    const { user } = useSession();
    const [created, setCreated] = useState('');
    if (user?.role !== 'restaurant_admin' || !user.restaurant_id)
        return (
            <>
                <Title title="Equipa" />
                <Empty text="A criação de membros da equipa está disponível ao administrador do restaurante." />
            </>
        );
    return (
        <>
            <Title
                title="Criar membro da equipa"
                description="A conta será associada automaticamente ao seu restaurante pelo servidor."
            />
            <Feedback message={created} success />
            <section className="max-w-lg rounded-2xl border bg-white p-6">
                <ApiForm
                    key={created}
                    fields={[
                        {
                            name: 'name',
                            label: 'Nome',
                            required: true,
                        },
                        {
                            name: 'email',
                            label: 'Email',
                            type: 'email',
                            required: true,
                        },
                        {
                            name: 'password',
                            label: 'Palavra-passe',
                            type: 'password',
                            required: true,
                        },
                        {
                            name: 'password_confirmation',
                            label: 'Confirmar palavra-passe',
                            type: 'password',
                            required: true,
                        },
                    ]}
                    submit={(data) => userApi.createMember(data)}
                    done={(result) =>
                        setCreated(
                            `Conta criada para ${(result as Resource<{ name: string }>).data.name}.`,
                        )
                    }
                    label="Criar membro"
                />
            </section>
            <p className="mt-4 text-xs text-slate-400">
                A API atual permite criar membros; não disponibiliza uma
                listagem ou edição de utilizadores da equipa.
            </p>
        </>
    );
}

import {
    ApiForm,
    Title,
    type Field,
    buttonClass,
} from '@/shared/components/api-ui';
import { useSession } from '@/domains/users/context/session';
import { userApi } from '../services';
import { useState } from 'react';
export default function Authentication({
    mode,
}: {
    mode: 'login' | 'register' | 'forgot';
}) {
    const { authenticate, user } = useSession();
    const [notice, setNotice] = useState('');
    const register = mode === 'register';
    const fields: Field[] = [
        ...(register
            ? [
                  {
                      name: 'name',
                      label: 'Nome completo',
                      required: true,
                      minLength: 2,
                      maxLength: 255,
                  },
              ]
            : []),
        {
            name: 'email',
            label: 'Email',
            type: 'email',
            required: true,
            maxLength: 255,
        },
        ...(mode === 'forgot'
            ? []
            : [
                  {
                      name: 'password',
                      label: 'Palavra-passe',
                      type: 'password' as const,
                      required: true,
                      ...(register
                          ? { minLength: 12, hint: 'Pelo menos 12 caracteres.' }
                          : {}),
                  },
              ]),
        ...(register
            ? [
                  {
                      name: 'password_confirmation',
                      label: 'Confirmar palavra-passe',
                      type: 'password' as const,
                      required: true,
                      minLength: 12,
                  },
              ]
            : []),
    ];
    return (
        <div className="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
            <Title
                title={
                    register
                        ? 'Criar conta'
                        : mode === 'forgot'
                          ? 'Recuperar acesso'
                          : 'Entrar'
                }
                description={
                    register
                        ? 'Crie a sua conta de cliente para fazer pedidos.'
                        : 'Aceda à sua conta DeliveryHub.'
                }
            />
            {notice ? (
                <>
                    <p
                        role="status"
                        className="text-sm leading-6 text-emerald-700"
                    >
                        {notice}
                    </p>
                    <a href="#login" className={`${buttonClass} mt-5`}>
                        Voltar ao login
                    </a>
                </>
            ) : user ? (
                <>
                    <p className="text-sm text-slate-500">
                        Já tem uma sessão ativa.
                    </p>
                    <a href="#dashboard" className={`${buttonClass} mt-4`}>
                        Abrir a minha área
                    </a>
                </>
            ) : (
                <ApiForm
                    key={mode}
                    fields={fields}
                    label={
                        register
                            ? 'Criar conta'
                            : mode === 'forgot'
                              ? 'Enviar instruções'
                              : 'Entrar'
                    }
                    submit={(data) =>
                        mode === 'forgot'
                            ? userApi.forgotPassword(data)
                            : authenticate(mode, data)
                    }
                    done={(result) => {
                        if (mode === 'forgot')
                            setNotice((result as { message: string }).message);
                        else window.location.hash = 'dashboard';
                    }}
                />
            )}
            {!user && !notice && (
                <div className="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
                    {register ? 'Já tem conta? ' : 'Ainda não tem conta? '}
                    <a
                        href={register ? '#login' : '#register'}
                        className="font-medium text-orange-700"
                    >
                        {register ? 'Entrar' : 'Criar conta'}
                    </a>
                    {mode === 'login' && (
                        <p className="mt-3">
                            <a
                                href="#forgot"
                                className="text-xs text-slate-400 underline"
                            >
                                Esqueceu-se da palavra-passe?
                            </a>
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}

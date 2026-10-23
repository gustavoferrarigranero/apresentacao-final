import { expect, it } from 'vite-plus/test';
import { fieldLabel, friendlyMessage, httpMessage } from '../messages';

it('personaliza os campos obrigatórios devolvidos pelo Laravel', () => {
    expect(friendlyMessage('The email field is required.')).toBe(
        'Preencha o campo «Email».',
    );
    expect(
        friendlyMessage('The admin email field is required.', 'admin_email'),
    ).toBe('Preencha o campo «Email do administrador».');
    expect(fieldLabel('items.1.quantity')).toBe('Quantidade do prato 2');
});
it('traduz formato, unicidade e confirmação sem validar o formulário no cliente', () => {
    expect(
        friendlyMessage('The email field must be a valid email address.'),
    ).toBe('Introduza um endereço de email válido.');
    expect(friendlyMessage('The email has already been taken.')).toContain(
        'Já existe',
    );
    expect(
        friendlyMessage('The password field confirmation does not match.'),
    ).toContain('não corresponde');
});
it('mantém os limites definidos na resposta do servidor', () => {
    expect(
        friendlyMessage('The password field must be at least 12 characters.'),
    ).toContain('12 caracteres');
    expect(
        friendlyMessage(
            'The address field must not be greater than 500 characters.',
        ),
    ).toContain('500 caracteres');
    expect(friendlyMessage('The price field must be at least 0.01.')).toContain(
        '0,01',
    );
});
it('apresenta erros de autenticação e de códigos em português', () => {
    expect(
        friendlyMessage('These credentials do not match our records.'),
    ).toContain('email ou a palavra-passe');
    expect(
        friendlyMessage(
            'The provided two factor authentication code was invalid.',
        ),
    ).toContain('código de autenticação');
    expect(
        friendlyMessage('The provided recovery code was invalid.'),
    ).toContain('código de recuperação');
});
it('preserva mensagens de negócio já legíveis', () => {
    expect(
        friendlyMessage('Stock insuficiente para o produto Prato do dia.'),
    ).toBe('Stock insuficiente para o produto Prato do dia.');
});
it('não apresenta informação técnica do servidor', () => {
    expect(
        friendlyMessage('SQLSTATE[HY000]: database users failed'),
    ).not.toContain('SQLSTATE');
    expect(httpMessage(500, 'Sensitive backend details', {})).not.toContain(
        'Sensitive',
    );
});
it('distingue conta inativa, falta de permissão e fim de sessão', () => {
    expect(httpMessage(403, 'A conta está inativa.', {})).toContain(
        'conta está inativa',
    );
    expect(httpMessage(403, 'Forbidden', {})).toContain('não tem permissão');
    expect(httpMessage(401, undefined, {})).toContain('sessão terminou');
    expect(httpMessage(401, undefined, {}, true)).toContain(
        'email ou a palavra-passe',
    );
});
it('separa resumo de validação, erro de stock e limite de tentativas', () => {
    expect(
        httpMessage(422, 'The email field is required. (and 1 more error)', {
            email: 'Preencha o email',
        }),
    ).not.toContain('and 1 more');
    expect(httpMessage(422, 'Stock insuficiente.', {})).toBe(
        'Stock insuficiente.',
    );
    expect(httpMessage(429, undefined, {})).toContain('Aguarde');
    expect(httpMessage(404, undefined, {})).toContain('já não está disponível');
});

const labels: Record<string, string> = {
    name: 'Nome',
    email: 'Email',
    password: 'Palavra-passe',
    password_confirmation: 'Confirmação da palavra-passe',
    current_password: 'Palavra-passe atual',
    admin_name: 'Nome do administrador',
    admin_email: 'Email do administrador',
    admin_password: 'Palavra-passe do administrador',
    address: 'Morada',
    phone_number: 'Contacto',
    nif: 'NIF',
    restaurant_id: 'Restaurante',
    category_id: 'Categoria',
    product_id: 'Prato',
    price: 'Preço',
    quantity: 'Quantidade',
    status: 'Estado',
    type: 'Tipo',
    delivery_date: 'Data de entrega',
    items: 'Pratos do pedido',
    code: 'Código de autenticação',
    recovery_code: 'Código de recuperação',
    token: 'Código de recuperação',
};
export function fieldLabel(field: string) {
    const item = /^items\.(\d+)\.(.+)$/.exec(field);
    if (item)
        return `${labels[item[2]] ?? 'Campo'} do prato ${Number(item[1]) + 1}`;
    return labels[field.replaceAll(' ', '_')] ?? 'Campo';
}
const technical =
    /SQLSTATE|stack trace|(?:[\w\\]+)?Exception\b|\/vendor\/|\/app\/|Auth guard|undefined (?:variable|index)|Call to|syntax error/i;

/** Presentation only: validation and authorization remain on the server. */
export function friendlyMessage(message: string, field?: string): string {
    const text = message.trim();
    if (!text)
        return 'Não foi possível concluir esta operação. Tente novamente.';
    if (technical.test(text))
        return 'Ocorreu um problema no serviço. Tente novamente mais tarde.';
    if (/failed to fetch|networkerror|network request failed/i.test(text))
        return 'Não foi possível contactar o servidor. Verifique a ligação e tente novamente.';
    if (
        /operation either timed out or was not allowed|NotAllowedError/i.test(
            text,
        )
    )
        return 'Não foi possível confirmar a chave de acesso. Tente novamente ou entre com o seu email e palavra-passe.';
    if (/user aborted a request|operation was aborted/i.test(text))
        return 'A operação foi cancelada. Pode tentar novamente quando quiser.';
    if (
        /these credentials do not match|auth\.failed|invalid credentials/i.test(
            text,
        )
    )
        return 'O email ou a palavra-passe estão incorretos. Verifique os dados e tente novamente.';
    if (/password is incorrect|password is invalid|auth\.password/i.test(text))
        return 'A palavra-passe indicada está incorreta.';
    if (
        /two factor authentication code was invalid|provided.*authentication code.*invalid/i.test(
            text,
        )
    )
        return 'O código de autenticação está incorreto ou expirou. Introduza um novo código.';
    if (/recovery code was invalid/i.test(text))
        return 'O código de recuperação está incorreto ou já foi utilizado.';
    if (/passwords\.token|password reset token is invalid/i.test(text))
        return 'O link de recuperação é inválido ou expirou. Solicite um novo link.';
    if (/passwords\.user|can.t find a user with that email/i.test(text))
        return 'Não foi possível encontrar uma conta com esse email.';
    if (
        /too many.*attempt|please try again in|passwords\.throttled/i.test(text)
    )
        return 'Foram feitas demasiadas tentativas. Aguarde um momento antes de tentar novamente.';
    if (/^Unauthenticated\.?$/i.test(text))
        return 'A sua sessão terminou. Entre novamente para continuar.';
    if (/^Forbidden\.?$|unauthorized action|not authorized/i.test(text))
        return 'A sua conta não tem permissão para realizar esta ação.';
    if (/^Not Found\.?$|no query results for model/i.test(text))
        return 'O registo já não está disponível. Volte à listagem e atualize os dados.';
    const attribute =
        field ??
        /^The (.+?) field\b/i.exec(text)?.[1] ??
        /^The (.+?) (?:has|must|confirmation|is)\b/i.exec(text)?.[1];
    const label = fieldLabel(attribute ?? '');
    if (/\bis required\b|validation\.required/i.test(text))
        return `Preencha o campo «${label}».`;
    if (/valid email|validation\.email/i.test(text))
        return 'Introduza um endereço de email válido.';
    if (/already been taken|validation\.unique/i.test(text))
        return `Já existe um registo com este valor no campo «${label}».`;
    if (
        /confirmation does not match|confirmation field must match|validation\.confirmed/i.test(
            text,
        )
    )
        return 'A confirmação da palavra-passe não corresponde à palavra-passe indicada.';
    let match = /at least (\d+) characters/i.exec(text);
    if (match)
        return `O campo «${label}» deve ter pelo menos ${match[1]} caracteres.`;
    match = /(?:greater than|max(?:imum)? of) (\d+) characters/i.exec(text);
    if (match)
        return `O campo «${label}» deve ter no máximo ${match[1]} caracteres.`;
    if (/must be an integer|validation\.integer/i.test(text))
        return `Indique um número inteiro no campo «${label}».`;
    if (/must be a number|validation\.numeric/i.test(text))
        return `Indique um valor numérico no campo «${label}».`;
    match = /must be at least (\d+(?:\.\d+)?)(?:\.|$)/i.exec(text);
    if (match)
        return `O campo «${label}» deve ser igual ou superior a ${match[1].replace('.', ',')}.`;
    match = /must not be greater than (\d+(?:\.\d+)?)(?:\.|$)/i.exec(text);
    if (match)
        return `O campo «${label}» deve ser igual ou inferior a ${match[1].replace('.', ',')}.`;
    if (/date after or equal to today/i.test(text))
        return 'Escolha a data de hoje ou uma data futura para a entrega.';
    if (/valid date|must be a date/i.test(text))
        return `Indique uma data válida no campo «${label}».`;
    if (/selected .+ is invalid|validation\.exists|validation\.in$/i.test(text))
        return `A opção do campo «${label}» já não está disponível. Atualize a lista e escolha novamente.`;
    if (/has a duplicate value|validation\.distinct/i.test(text))
        return 'O pedido contém pratos repetidos. Reveja as quantidades no carrinho.';
    if (/^The .+ field\b|^validation\./i.test(text))
        return `Verifique o campo «${label}» e tente novamente.`;
    return text;
}
export function httpMessage(
    status: number,
    message: string | undefined,
    fields: Record<string, string>,
    login = false,
) {
    if (status >= 500)
        return 'Não foi possível concluir a operação devido a um problema no serviço. Tente novamente mais tarde.';
    if (status === 401)
        return login
            ? 'O email ou a palavra-passe estão incorretos.'
            : 'A sua sessão terminou. Entre novamente para continuar.';
    if (status === 403 && message && /inactive|inativ|desativ/i.test(message))
        return 'A sua conta está inativa. Contacte o administrador para recuperar o acesso.';
    if (status === 403)
        return 'A sua conta não tem permissão para realizar esta ação. Se precisar de acesso, contacte o administrador.';
    if (status === 404)
        return 'O registo procurado já não está disponível. Volte à listagem e atualize os dados.';
    if (status === 419)
        return 'A sessão expirou. Atualize a página e tente novamente.';
    if (status === 429)
        return 'Foram feitas demasiadas tentativas. Aguarde um momento antes de tentar novamente.';
    if (status === 422 && Object.keys(fields).length)
        return 'Não foi possível guardar os dados. Corrija os campos assinalados e tente novamente.';
    if (message) return friendlyMessage(message);
    return 'Não foi possível concluir a operação. Tente novamente.';
}

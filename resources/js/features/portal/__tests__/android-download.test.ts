import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { expect, it } from 'vite-plus/test';
import { androidDownloadLink } from '../config/android-download';
import AndroidDownload from '../components/android-download';
it('aceita APK local ou publicado por HTTPS', () => {
    expect(androidDownloadLink('/downloads/deliveryhub.apk')).toEqual({
        href: '/downloads/deliveryhub.apk',
        kind: 'apk',
        local: true,
    });
    expect(
        androidDownloadLink('https://cdn.example.test/app.apk?version=1')?.kind,
    ).toBe('apk');
});
it('aceita página de aplicação na Play Store com identificador', () => {
    expect(
        androidDownloadLink(
            'https://play.google.com/store/apps/details?id=test.deliveryhub',
        )?.kind,
    ).toBe('store');
    expect(
        androidDownloadLink('https://play.google.com/store/apps/details'),
    ).toBeNull();
});
it('não oferece links vazios, esquemas executáveis ou ficheiros de outro tipo', () => {
    for (const input of [
        undefined,
        '',
        'javascript:alert(1)',
        'data:text/html,test',
        '//untrusted.test/app.apk',
        '/\\untrusted.test/app.apk',
        'https://example.test/app.exe',
        'http://example.test/app.apk',
        'app.apk',
        'https://user:password@example.test/app.apk',
    ])
        expect(androidDownloadLink(input)).toBeNull();
});
it('sem aplicação configurada apresenta disponibilidade futura sem download falso', () => {
    const html = renderToStaticMarkup(
        createElement(AndroidDownload, { url: '' }),
    );
    expect(html).toContain('Disponível em breve');
    expect(html).toContain('disabled=""');
    expect(html).not.toContain('href=');
});
it('APK configurado apresenta chamada de download com o endereço correto', () => {
    const html = renderToStaticMarkup(
        createElement(AndroidDownload, { url: '/downloads/deliveryhub.apk' }),
    );
    expect(html).toContain('href="/downloads/deliveryhub.apk"');
    expect(html).toContain('download="DeliveryHub.apk"');
    expect(html).toContain('Baixar para Android');
});

import { Download, Smartphone, ArrowUpRight } from 'lucide-react';
import { androidDownloadLink } from '../config/android-download';
import { buttonClass } from '@/shared/components/api-ui';

export default function AndroidDownload({
    url = import.meta.env.VITE_ANDROID_APP_URL,
}: {
    url?: string;
}) {
    const link = androidDownloadLink(url);
    return (
        <section
            aria-labelledby="android-download-title"
            className="mb-9 flex flex-col gap-5 rounded-2xl border border-orange-100 bg-orange-50/70 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6"
        >
            <div className="flex items-start gap-4">
                <span className="rounded-2xl bg-white p-3 text-orange-600 shadow-sm">
                    <Smartphone size={26} aria-hidden="true" />
                </span>
                <div>
                    <p className="mb-1 text-[10px] font-semibold tracking-widest text-orange-700 uppercase">
                        Aplicação Android
                    </p>
                    <h2
                        id="android-download-title"
                        className="text-lg font-semibold text-slate-900"
                    >
                        DeliveryHub no seu telemóvel
                    </h2>
                    <p className="mt-1 max-w-lg text-sm leading-6 text-slate-600">
                        {link
                            ? 'Aceda à aplicação Android e tenha os seus pedidos sempre à mão.'
                            : 'A aplicação para Android estará disponível aqui em breve. Pode continuar a pedir pelo portal.'}
                    </p>
                </div>
            </div>
            <div className="shrink-0">
                {link ? (
                    <a
                        href={link.href}
                        className={`${buttonClass} w-full sm:w-auto`}
                        download={link.local ? 'DeliveryHub.apk' : undefined}
                        target={link.kind === 'store' ? '_blank' : undefined}
                        rel={
                            link.kind === 'store'
                                ? 'noopener noreferrer'
                                : undefined
                        }
                    >
                        {link.kind === 'store' ? (
                            <ArrowUpRight size={17} aria-hidden="true" />
                        ) : (
                            <Download size={17} aria-hidden="true" />
                        )}
                        {link.kind === 'store'
                            ? 'Obter na Google Play'
                            : 'Baixar para Android'}
                    </a>
                ) : (
                    <button
                        type="button"
                        disabled
                        className={`${buttonClass} w-full sm:w-auto`}
                    >
                        Disponível em breve
                    </button>
                )}
                {link?.kind === 'apk' && (
                    <p className="mt-2 text-center text-[11px] text-slate-500">
                        Ficheiro APK · Instalação no Android
                    </p>
                )}
            </div>
        </section>
    );
}

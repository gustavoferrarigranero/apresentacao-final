import { useCart } from '@/domains/orders/hooks/use-cart';
import { useView } from './use-view';
import { Head } from '@inertiajs/react';
import { lazy, Suspense, useCallback, useState } from 'react';
import {
    Bike,
    ClipboardList,
    LayoutDashboard,
    LogOut,
    Menu,
    Package,
    ShoppingBag,
    Store,
    Tags,
    Users,
    X,
} from 'lucide-react';
import {
    ApiSessionProvider,
    useSession,
} from '@/domains/users/context/session';
import { useMutation } from '@/shared/hooks/use-api';
import { cartReducer } from '@/domains/orders/cart';
import { isTeam, roleLabels } from '@/domains/users/permissions';
import {
    buttonClass,
    Feedback,
    secondaryClass,
} from '@/shared/components/api-ui';

import Portal from '@/features/portal/pages/portal';

const Connection = lazy(() => import('@/domains/orders/components/connection'));
const Cart = lazy(() => import('@/domains/orders/pages/cart'));
const Checkout = lazy(() => import('@/domains/orders/pages/checkout'));
const Authentication = lazy(() => import('@/domains/users/pages/auth'));
const Dashboard = lazy(() => import('@/features/dashboard/pages/dashboard'));
const Orders = lazy(() => import('@/domains/orders/pages/orders'));
const CategoriesManagement = lazy(() =>
    import('@/domains/categories/pages/categories').then((module) => ({
        default: module.CategoriesManagement,
    })),
);
const ProductsManagement = lazy(() =>
    import('@/domains/products/pages/products').then((module) => ({
        default: module.ProductsManagement,
    })),
);
const RestaurantsManagement = lazy(() =>
    import('@/domains/restaurants/pages/restaurants').then((module) => ({
        default: module.RestaurantsManagement,
    })),
);
const TeamManagement = lazy(() =>
    import('@/domains/users/pages/team').then((module) => ({
        default: module.TeamManagement,
    })),
);
function Shell({ initial }: { initial: string }) {
    const { user, loading, error, retry, logout } = useSession();
    const view = useView(initial);
    const [revision, setRevision] = useState(0);
    const changed = useCallback(() => setRevision((value) => value + 1), []);
    const [menu, setMenu] = useState(false);
    const [notice, setNotice] = useState('');
    const [cart, dispatch] = useCart(user, loading);
    const logOut = useMutation();
    const count = cart.items.reduce((sum, item) => sum + item.quantity, 0);
    const commerce =
        view === 'portal' ||
        view.startsWith('restaurant/') ||
        ['cart', 'checkout', 'login', 'register', 'forgot'].includes(view);
    const links = [
        { title: 'Dashboard', href: 'dashboard', icon: LayoutDashboard },
        ...(user?.role === 'super_admin' || user?.role === 'restaurant_admin'
            ? [{ title: 'Restaurantes', href: 'restaurants', icon: Store }]
            : []),
        ...(isTeam(user)
            ? [
                  { title: 'Categorias', href: 'categories', icon: Tags },
                  { title: 'Produtos', href: 'products', icon: Package },
              ]
            : []),
        ...(user?.role === 'customer' || isTeam(user)
            ? [{ title: 'Pedidos', href: 'orders', icon: ClipboardList }]
            : []),
        ...(user?.role === 'restaurant_admin'
            ? [{ title: 'Equipa', href: 'team', icon: Users }]
            : []),
        { title: 'Portal do cliente', href: 'portal', icon: ShoppingBag },
    ];
    let content;
    if (view === 'portal' || /^restaurant\/\d+$/.test(view))
        content = (
            <Portal
                items={cart.items}
                restaurantId={
                    view.startsWith('restaurant/')
                        ? Number(view.split('/')[1])
                        : undefined
                }
                onAdd={(product) => {
                    const result = cartReducer(cart, { type: 'add', product });
                    dispatch({ type: 'add', product });
                    setNotice(
                        result.error
                            ? ''
                            : `${product.name} adicionado ao carrinho.`,
                    );
                }}
            />
        );
    else if (view === 'cart')
        content = <Cart cart={cart} dispatch={dispatch} />;
    else if (view === 'checkout')
        content = (
            <Checkout
                items={cart.items}
                clear={() => dispatch({ type: 'clear' })}
            />
        );
    else if (['login', 'register', 'forgot'].includes(view))
        content = (
            <Authentication mode={view as 'login' | 'register' | 'forgot'} />
        );
    else if (loading)
        content = (
            <p role="status" className="py-12 text-sm text-slate-500">
                A restaurar a sessão…
            </p>
        );
    else if (!user)
        content = (
            <div className="rounded-xl border bg-white p-8">
                <h1 className="text-xl font-semibold">
                    Entre para aceder à sua área
                </h1>
                <a href="#login" className={`${buttonClass} mt-5`}>
                    Entrar
                </a>
            </div>
        );
    else if (view === 'dashboard') content = <Dashboard revision={revision} />;
    else if (view === 'orders' || /^order\/\d+$/.test(view))
        content = (
            <Orders
                id={
                    view.startsWith('order/')
                        ? Number(view.split('/')[1])
                        : undefined
                }
                revision={revision}
                changed={changed}
            />
        );
    else if (view === 'restaurants') content = <RestaurantsManagement />;
    else if (view === 'categories') content = <CategoriesManagement />;
    else if (view === 'products') content = <ProductsManagement />;
    else if (view === 'team') content = <TeamManagement />;
    else
        content = (
            <div>
                <h1 className="text-xl font-semibold">Página não encontrada</h1>
                <a href="#portal" className={`${secondaryClass} mt-4`}>
                    Voltar ao portal
                </a>
            </div>
        );
    return (
        <div className="flex min-h-svh flex-col bg-slate-50 text-slate-900">
            <Head title={commerce ? 'Portal do cliente' : 'Gestão'} />
            <a
                href="#deliveryhub-content"
                onClick={(event) => {
                    event.preventDefault();
                    document.getElementById('deliveryhub-content')?.focus();
                }}
                className="sr-only bg-white p-3 focus:not-sr-only"
            >
                Saltar para o conteúdo
            </a>
            <header className="sticky top-0 z-20 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white/95 px-5 py-4 backdrop-blur sm:px-8">
                <div className="flex items-center gap-3">
                    {user && !commerce && (
                        <button
                            className="p-2 md:hidden"
                            aria-label={menu ? 'Fechar menu' : 'Abrir menu'}
                            aria-expanded={menu}
                            onClick={() => setMenu((current) => !current)}
                        >
                            {menu ? <X size={20} /> : <Menu size={20} />}
                        </button>
                    )}
                    <a
                        href="#portal"
                        className="inline-flex items-center gap-2 text-lg font-bold tracking-tight"
                    >
                        <span className="rounded-xl bg-orange-600 p-2 text-white">
                            <Bike size={23} />
                        </span>
                        Delivery
                        <span className="-ml-2 text-orange-600">Hub</span>
                    </a>
                </div>
                <nav
                    aria-label="Navegação da conta"
                    className="flex items-center gap-3 text-xs"
                >
                    {user ? (
                        <>
                            <a
                                href="#dashboard"
                                className="hidden text-slate-600 sm:block"
                            >
                                {user.name}
                            </a>
                            <a href="#dashboard" className="text-slate-500">
                                A minha área
                            </a>
                            <button
                                disabled={logOut.busy}
                                className="inline-flex items-center gap-1 text-slate-400"
                                onClick={() =>
                                    void logOut.run(logout, () => {
                                        dispatch({ type: 'clear' });
                                        setNotice('');
                                        window.location.hash = 'portal';
                                    })
                                }
                            >
                                <LogOut size={15} />
                                <span className="hidden sm:inline">Sair</span>
                            </button>
                        </>
                    ) : (
                        <a href="#login" className="text-slate-600">
                            Entrar / Criar conta
                        </a>
                    )}
                    <a
                        href="#cart"
                        aria-label={`Carrinho com ${count} unidades`}
                        className="inline-flex items-center gap-2 rounded-full bg-orange-600 px-4 py-2.5 font-medium text-white"
                    >
                        <ShoppingBag size={16} />
                        Carrinho ({count})
                    </a>
                </nav>
            </header>
            <div className="flex flex-1 flex-col md:flex-row">
                {user && !commerce && (
                    <aside
                        className={`${menu ? 'block' : 'hidden'} border-b border-slate-200 bg-white p-4 md:block md:w-56 md:shrink-0 md:border-r md:border-b-0`}
                    >
                        <p className="px-3 py-4 text-[10px] font-semibold tracking-widest text-slate-400 uppercase">
                            {roleLabels[user.role]}
                        </p>
                        <nav aria-label="Menu lateral" className="space-y-1">
                            {links.map(({ title, href, icon: Icon }) => (
                                <a
                                    href={`#${href}`}
                                    key={href}
                                    aria-current={
                                        view === href ? 'page' : undefined
                                    }
                                    onClick={() => {
                                        setMenu(false);
                                        setNotice('');
                                    }}
                                    className={`flex items-center gap-3 rounded-lg px-3 py-3 text-xs font-medium ${view === href ? 'bg-orange-50 text-orange-700' : 'text-slate-500 hover:bg-slate-50'}`}
                                >
                                    <Icon size={18} />
                                    {title}
                                </a>
                            ))}
                        </nav>
                    </aside>
                )}
                <main
                    id="deliveryhub-content"
                    tabIndex={-1}
                    className={`mx-auto w-full min-w-0 flex-1 px-5 py-8 sm:px-8 ${commerce ? 'max-w-7xl' : 'max-w-[1400px]'}`}
                >
                    <Feedback message={error} />
                    {error && (
                        <button
                            className={`${secondaryClass} mb-4`}
                            onClick={retry}
                        >
                            Verificar sessão
                        </button>
                    )}
                    <Feedback message={logOut.error} />
                    <Feedback message={cart.error} />
                    <p role="status" className="mb-4 text-xs text-emerald-700">
                        {notice}
                    </p>
                    <div key={`${view}:${user?.id ?? 'guest'}`}>
                        <Suspense
                            fallback={
                                <p
                                    role="status"
                                    className="py-8 text-sm text-slate-500"
                                >
                                    A carregar a página…
                                </p>
                            }
                        >
                            {content}
                        </Suspense>
                    </div>
                </main>
            </div>
            <footer className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-white px-6 py-5 text-xs text-slate-400">
                <span>© {new Date().getFullYear()} DeliveryHub</span>
                {user && (
                    <Suspense fallback={null}>
                        <Connection key={user.id} changed={changed} />
                    </Suspense>
                )}
            </footer>
        </div>
    );
}
export default function DeliveryHub({
    initial = 'portal',
}: {
    initial?: string;
}) {
    return (
        <ApiSessionProvider>
            <Shell initial={initial} />
        </ApiSessionProvider>
    );
}

import { restaurantEndpoints } from '@/domains/restaurants/services';
import { productEndpoints } from '@/domains/products/services';
import { useState } from 'react';
import {
    ArrowLeft,
    ArrowRight,
    MapPin,
    Plus,
    ShoppingBag,
    UtensilsCrossed,
} from 'lucide-react';
import { useList, useResource } from '@/shared/hooks/use-api';
import type { Product } from '@/domains/products/types';
import type { Restaurant } from '@/domains/restaurants/types';
import type { Resource } from '@/shared/api/types';
import { decimalToCents, money } from '@/shared/utils/money';
import {
    Title,
    SearchBox,
    Pagination,
    LoadState,
    Empty,
    buttonClass,
    secondaryClass,
} from '@/shared/components/api-ui';
export default function Portal({
    restaurantId,
    onAdd,
}: {
    restaurantId?: number;
    onAdd: (product: Product) => void;
}) {
    const [search, setSearch] = useState('');
    const restaurants = useList<Restaurant>(
        !restaurantId ? restaurantEndpoints.list : null,
        0,
        12,
        search,
    );
    const detail = useResource<Resource<Restaurant>>(
        restaurantId ? restaurantEndpoints.detail(restaurantId) : null,
    );
    const products = useList<Product>(
        restaurantId && detail.value
            ? productEndpoints.catalog(restaurantId)
            : null,
        0,
        12,
        search,
    );
    if (!restaurantId)
        return (
            <>
                <section className="mb-9 overflow-hidden rounded-3xl bg-slate-900 p-8 text-white sm:p-12">
                    <p className="mb-3 text-xs tracking-widest text-orange-300 uppercase">
                        À sua mesa, com sabor
                    </p>
                    <h1 className="max-w-xl text-3xl leading-tight font-semibold sm:text-5xl">
                        O seu próximo favorito
                        <br />
                        <span className="text-orange-300">está aqui.</span>
                    </h1>
                    <p className="mt-5 max-w-lg text-sm leading-7 text-white/65">
                        Escolha um restaurante, descubra o menu e faça o seu
                        pedido.
                    </p>
                    <a
                        href="#cart"
                        className="mt-6 inline-flex items-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-medium text-slate-900"
                    >
                        <ShoppingBag size={17} />O meu carrinho
                    </a>
                </section>
                <Title
                    title="Escolha o restaurante"
                    description="Catálogo publicado pelos restaurantes na plataforma."
                />
                <SearchBox onSearch={setSearch} />
                <LoadState {...restaurants} retry={restaurants.retry} />
                {!restaurants.loading && !restaurants.error && (
                    <>
                        <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            {restaurants.value?.data.map((restaurant) => (
                                <a
                                    key={restaurant.id}
                                    href={`#restaurant/${restaurant.id}`}
                                    className="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-orange-300 hover:shadow-md"
                                >
                                    <span className="mb-6 inline-flex rounded-xl bg-orange-50 p-3 text-orange-600">
                                        <UtensilsCrossed size={25} />
                                    </span>
                                    <h2 className="text-lg font-semibold">
                                        {restaurant.name}
                                    </h2>
                                    <p className="mt-3 flex items-start gap-2 text-xs leading-6 text-slate-500">
                                        <MapPin
                                            size={15}
                                            className="mt-1 shrink-0"
                                        />
                                        {restaurant.address}
                                    </p>
                                    <span className="mt-5 inline-flex items-center gap-2 text-xs font-semibold text-orange-700">
                                        Ver menu
                                        <ArrowRight size={15} />
                                    </span>
                                </a>
                            ))}
                        </div>
                        {!restaurants.value?.data.length && (
                            <Empty text="Nenhum restaurante encontrado." />
                        )}
                        <Pagination
                            data={restaurants.value}
                            go={restaurants.go}
                        />
                    </>
                )}
            </>
        );
    return (
        <>
            <a
                href="#portal"
                className="mb-5 inline-flex items-center gap-2 text-xs text-slate-500"
            >
                <ArrowLeft size={15} />
                Todos os restaurantes
            </a>
            <LoadState {...detail} retry={detail.retry} />
            {detail.value && (
                <>
                    <Title
                        title={detail.value.data.name}
                        description={`${detail.value.data.address} · ${detail.value.data.phone_number}`}
                    />
                    <SearchBox
                        key={restaurantId}
                        onSearch={setSearch}
                        placeholder="Pesquisar produtos neste restaurante…"
                    />
                    <LoadState {...products} retry={products.retry} />
                    {!products.loading && !products.error && (
                        <>
                            <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                                {products.value?.data.map((product) => (
                                    <article
                                        key={product.id}
                                        className="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                                    >
                                        <div className="flex h-32 items-center justify-center bg-gradient-to-br from-orange-50 to-amber-50 text-orange-200">
                                            <UtensilsCrossed
                                                size={45}
                                                strokeWidth={1.2}
                                                aria-hidden="true"
                                            />
                                        </div>
                                        <div className="flex flex-1 flex-col p-5">
                                            <p className="text-[10px] font-semibold tracking-wider text-orange-700 uppercase">
                                                {product.category?.name ??
                                                    product.type}
                                            </p>
                                            <h2 className="mt-2 text-base font-semibold">
                                                {product.name}
                                            </h2>
                                            <p className="mt-2 text-xs text-slate-400">
                                                {product.quantity > 0
                                                    ? `${product.quantity} disponíveis`
                                                    : 'Stock esgotado'}
                                            </p>
                                            <div className="mt-6 flex items-center justify-between gap-3">
                                                <strong className="text-lg">
                                                    {money(
                                                        decimalToCents(
                                                            product.price,
                                                        ),
                                                    )}
                                                </strong>
                                                <button
                                                    className={`${buttonClass} min-h-9 rounded-full text-xs`}
                                                    disabled={
                                                        product.quantity <= 0 ||
                                                        product.status !==
                                                            'active'
                                                    }
                                                    onClick={() =>
                                                        onAdd(product)
                                                    }
                                                    aria-label={`Adicionar ${product.name}`}
                                                >
                                                    <Plus size={15} />
                                                    Adicionar
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                            {!products.value?.data.length && (
                                <Empty text="Este restaurante ainda não tem produtos publicados para esta pesquisa." />
                            )}
                            <Pagination
                                data={products.value}
                                go={products.go}
                            />
                            <a
                                href="#cart"
                                className={`${secondaryClass} mt-6`}
                            >
                                Ver carrinho
                                <ArrowRight size={14} />
                            </a>
                        </>
                    )}
                </>
            )}
        </>
    );
}

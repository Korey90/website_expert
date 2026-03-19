import { Head } from '@inertiajs/react';
import Navbar  from '@/Components/Marketing/Navbar';
import Footer  from '@/Components/Marketing/Footer';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function CmsPage({ auth, page, navbar, footer }) {
    useScrollReveal('.reveal');

    const metaTitle = page.meta_title || page.title;
    const metaDesc  = page.meta_description || '';

    return (
        <>
            <Head>
                <title>{metaTitle} – WebsiteExpert</title>
                {metaDesc && <meta name="description" content={metaDesc} />}
            </Head>

            <div className="min-h-screen bg-white dark:bg-neutral-950 text-neutral-900 dark:text-white flex flex-col">
                <Navbar auth={auth} data={navbar} />

                <main className="flex-1 pt-24 pb-20">
                    <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">

                        <h1 className="font-display text-3xl sm:text-4xl font-bold mb-8 text-neutral-900 dark:text-white">
                            {page.title}
                        </h1>

                        <div
                            className="prose prose-neutral dark:prose-invert max-w-none
                                       prose-headings:font-display
                                       prose-a:text-brand-500 prose-a:no-underline hover:prose-a:underline"
                            dangerouslySetInnerHTML={{ __html: page.content || '' }}
                        />
                    </div>
                </main>

                <Footer data={footer} />
            </div>
        </>
    );
}

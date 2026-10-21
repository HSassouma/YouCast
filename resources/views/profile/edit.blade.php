<x-app-layout width="read">
    <x-slot name="title">Mon profil — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            title="Mon profil"
            subtitle="Gérez vos informations, votre mot de passe et la suppression de votre compte." />
    </x-slot>

    <div>
        <x-flash />

        <div class="space-y-6">
            <section class="panel p-6 sm:p-7">
                @include('profile.partials.update-profile-information-form')
            </section>

            <section class="panel p-6 sm:p-7">
                @include('profile.partials.update-password-form')
            </section>

            <section class="panel border-danger-200 p-6 sm:p-7">
                @include('profile.partials.delete-user-form')
            </section>
        </div>
    </div>
</x-app-layout>

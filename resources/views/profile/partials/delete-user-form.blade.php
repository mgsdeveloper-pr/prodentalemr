<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-danger-button
        id="open-account-deletion"
        type="button"
        x-data
        x-on:click="$dispatch('open-account-deletion')"
    >{{ __('Delete Account') }}</x-danger-button>

    <x-pds.workflow-dialog
        labelled-by="account-deletion-title"
        :auto-open="$errors->userDeletion->isNotEmpty()"
        return-focus="open-account-deletion"
        width="36rem"
        x-on:open-account-deletion.window="open()"
    >
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 id="account-deletion-title" class="text-lg font-medium">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm" style="color: var(--pwdl-text-secondary, #475569)">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="account-deletion-password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="account-deletion-password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button autofocus x-on:click="$el.closest('dialog').close()">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-pds.workflow-dialog>
</section>

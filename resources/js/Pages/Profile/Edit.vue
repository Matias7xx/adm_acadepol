<script setup>
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import SiteNavbar from '../Components/SiteNavbar.vue';
import Footer from '../Components/Footer.vue';
import Header from '../Components/Header.vue';
import Toast from '../Components/Toast.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const activeTab = ref('profile'); // 'profile', 'security', 'account'

const formattedName = computed(() => {
    if (!user.name) return '';
    
    const names = user.name.trim().split(' ');
    if (names.length === 1) return names[0];
    
    return `${names[0]} ${names[names.length - 1]}`;
});
</script>

<template>
    <Head title="Perfil" />
    <SiteNavbar />
    <Header />
    
    <!-- Toast component para feedback -->
    <Toast />
    
    <main class="bg-gray-50 pb-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row gap-8">
                <!-- Sidebar / User Info -->
                <div class="w-full md:w-1/4">
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <!-- User Info -->
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-[#bea55a] text-white rounded-full h-12 w-12 flex items-center justify-center font-semibold text-lg">
                                    {{ formattedName.charAt(0) }}
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-lg font-medium text-gray-900">
                                        {{ formattedName }}
                                    </h2>
                                    <p class="text-sm text-gray-500">
                                        {{ user.matricula }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Navigation Tabs -->
                        <nav class="p-4">
                            <div class="space-y-1">
                                <button 
                                    @click="activeTab = 'profile'"
                                    class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                                    :class="activeTab === 'profile' ? 'bg-[#bea55a]/10 text-[#bea55a]' : 'text-gray-600 hover:bg-gray-100'"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Informações Pessoais
                                </button>
                                
                                <button 
                                    @click="activeTab = 'security'"
                                    class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                                    :class="activeTab === 'security' ? 'bg-[#bea55a]/10 text-[#bea55a]' : 'text-gray-600 hover:bg-gray-100'"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Segurança
                                </button>
                                <!-- Conta -->
                                <!-- <button 
                                    @click="activeTab = 'account'"
                                    class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                                    :class="activeTab === 'account' ? 'bg-[#bea55a]/10 text-[#bea55a]' : 'text-gray-600 hover:bg-gray-100'"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Conta
                                </button> -->
                            </div>
                        </nav>
                    </div>
                </div>
                
                <!-- Content Area -->
                <div class="w-full md:w-3/4 space-y-6">
                    <!-- Informações de perfil Tab -->
                    <div v-if="activeTab === 'profile'" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Informações Pessoais</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Atualize as informações da sua conta e seu endereço de e-mail.
                            </p>
                        </div>
                        
                        <div class="p-6">
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                                class="max-w-3xl"
                            />
                        </div>
                    </div>
                    
                    <!-- Segurança Tab -->
                    <div v-if="activeTab === 'security'" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Segurança</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Garanta que sua conta esteja usando uma senha forte e segura.
                            </p>
                        </div>
                        
                        <div class="p-6">
                            <UpdatePasswordForm class="max-w-3xl" />
                        </div>
                    </div>
                    
                    <!-- Conta Tab (excluir conta)-->
                    <!-- <div v-if="activeTab === 'account'" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Gerenciar Conta</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Gerencie as configurações da sua conta e opções de exclusão.
                            </p>
                        </div>
                        
                        <div class="p-6">
                            <DeleteUserForm class="max-w-3xl" />
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </main>
    
    <Footer />
</template>
<script>
    document.addEventListener('alpine:init', () => {
        window.caspianInitData = {
            // Данные пользователя
            myId: {{ auth()->id() }},           // Число (для сокетов)
            myHashid: "{{ auth()->user()->hashid }}", // Строка (для идентификации в WebRTC)
            myInterests: @js(auth()->user()->interests ?? []),
            currentLevel: {{ auth()->user()->level ?? 1 }},
            totalXp: {{ auth()->user()->xp ?? 0 }},
            
            // Настройки поиска/фильтров
            targetCountry: @js(auth()->user()->target_country ?? 'global'),
            targetGender: @js(auth()->user()->target_gender ?? 'all'),
            targetAgeMin: {{ auth()->user()->target_age_min ?? 18 }},
            targetAgeMax: {{ auth()->user()->target_age_max ?? 99 }},

            // Системные конфиги
            iceServers: @js(config('webrtc.ice_servers')),

            // МАТРИЦА ПЕРЕВОДОВ
            translations: {
                // Toasts & Notifications
                target_country_updated: "{{ __('app.Target_Country_Updated') }}",
                target_gender_updated: "{{ __('app.Target_Gender_Updated') }}",
                update_failed: "{{ __('app.Update_Failed') }}",
                save_failed: "{{ __('app.Save_Failed') }}",
                hardware_synced: "{{ __('app.Hardware_Synced') }}",
                device_error: "{{ __('app.Device_Error_Access_Denied') }}",
                camera_denied: "{{ __('app.Camera_Permission_Denied') }}",
                blacklisted: "{{ __('app.Blacklisted') }}",
                unblocked: "{{ __('app.Interlocutor_Unblocked') }}",
                call_ended: "{{ __('app.Call_Ended') }}",
                contact_unlinked: "{{ __('app.Contact_Unlinked') }}",
                contact_removed: "{{ __('app.Contact_Removed') }}",
                contact_added: "{{ __('app.Contact_Added') }}",
                remove_friend_sure: "{{ __('app.Remove_Friend_Sure') }}",
                request_encrypted: "{{ __('app.Request_Encryted_And_Send') }}",
                protocol_active: "{{ __('app.Protocol_Already_Active') }}",
                history_cleared: "{{ __('app.History_Terminated') }}",
                failed_to_load_history: "{{ __('app.Failed_To_Load_History') }}",
                identity_verified: "{{ __('app.Identity_Verified') }}",
                request_terminated: "{{ __('app.Request_Terminated') }}",
                user_busy: "{{ __('app.User_Is_Busy') }}",
                calling: "{{ __('app.Calling') }}",
                system_overload: "{{ __('app.System_Overload') }}",
                link_captured: "{{ __('rooms.Link_Captured') }}",
                report_desc: "{{ __('app.Report_Desc') }}",
                report_transmitted: "{{ __('app.Repoert_Transmitted_To_Shield') }}",
                contrast_filter_on: "{{ __('app.Contrast_Filter_On') }}",
                contrast_filter_off: "{{ __('app.Contrast_Filter_Off') }}",
                monochrome_filter_on: "{{ __('app.Monochrome_Filter_On') }}",
                monochrome_filter_off: "{{ __('app.Monochrome_Filter_Off') }}",
                
                // Словари
                male: "{{ __('chatroulette.Male') }}",
                female: "{{ __('chatroulette.Female') }}",
                all: "{{ __('app.Global_Match') }}",
                unknown_location: "{{ __('chatroulette.Unknown_Location') }}",
                years_old: "{{ __('chatroulette.Years_Old') }}"
            }
        };
    });
</script>
@stack('scripts')
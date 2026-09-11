@extends('layouts.app')

@section('title','AI Settings')

@section('page-title','AI Provider Settings')


@section('content')


<div class="welcome">

    <div>

        <h1>
            🤖 AI Processing Settings
        </h1>

        <p>
            Choose AI engine used for automatic paper marking.
        </p>

    </div>

</div>



<div class="admin-grid">


    {{-- AI SETTINGS FORM --}}
    <section class="panel">


        <div class="panel-head">

            <h2>
                ⚙️ AI Provider Configuration
            </h2>

        </div>



        <div style="padding:25px;">


        <form method="POST"
        action="{{ route('admin.ai-settings.update') }}">

            @csrf
            @method('PUT')



            <label>

                AI Provider


                <select class="input"
                name="provider"
                required>


                    <option value="adaptive"
                    @selected(($setting?->provider ?? 'adaptive')=='adaptive')>

                        Adaptive Local AI

                    </option>



                    <option value="openai"
                    @selected($setting?->provider=='openai')>

                        OpenAI

                    </option>



                    <option value="gemini"
                    @selected($setting?->provider=='gemini')>

                        Google Gemini

                    </option>



                    <option value="claude"
                    @selected($setting?->provider=='claude')>

                        Claude AI

                    </option>



                    <option value="azure"
                    @selected($setting?->provider=='azure')>

                        Azure OpenAI

                    </option>



                    <option value="openrouter"
                    @selected($setting?->provider=='openrouter')>

                        OpenRouter

                    </option>



                    <option value="ollama"
                    @selected($setting?->provider=='ollama')>

                        Ollama Local AI

                    </option>


                </select>

            </label>



            <br>



            <label>

                Model Name


                <input
                class="input"
                name="model"
                value="{{ $setting?->model ?? 'adaptive-local-v1' }}"
                placeholder="Example: gpt-5-mini">


            </label>



            <br>



            <label>

                API Key


                <input
                class="input"
                type="password"
                name="api_key"
                placeholder="{{ $setting?->api_key ? 'Saved - enter new key' : 'Enter API key' }}">


            </label>



            @if($setting?->api_key)

            <div class="info-note">

                ✅ API Key already configured.

                <br>

                Leave blank to keep existing key.

            </div>

            @endif



            <br>



            <label>

                Status


                <select class="input"
                name="status">


                    <option value="active"
                    @selected(($setting?->status ?? 'active')=='active')>

                        Active

                    </option>


                    <option value="inactive"
                    @selected($setting?->status=='inactive')>

                        Inactive

                    </option>


                </select>


            </label>



            <br>


            <button class="btn">

                💾 Save AI Configuration

            </button>


        </form>


        </div>


    </section>





    {{-- PROVIDERS INFO --}}

    <aside class="panel">


        <div class="panel-head">

            <h2>
                🚀 Supported AI Engines
            </h2>

        </div>


        <div style="padding:20px;">


            <ul class="feature-list">


                <li>
                    🤖 Adaptive Local AI
                    <span class="badge green">
                        Free
                    </span>
                </li>


                <li>
                    OpenAI GPT Models
                </li>


                <li>
                    Google Gemini
                </li>


                <li>
                    Claude AI
                </li>


                <li>
                    Azure OpenAI
                </li>


                <li>
                    OpenRouter Models
                </li>


                <li>
                    Ollama Local AI
                </li>


            </ul>


        </div>


    </aside>



</div>


@endsection
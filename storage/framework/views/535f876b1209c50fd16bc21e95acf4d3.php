<?php $__env->startSection('title','AI Settings'); ?>

<?php $__env->startSection('page-title','AI Provider Settings'); ?>


<?php $__env->startSection('content'); ?>


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


    
    <section class="panel">


        <div class="panel-head">

            <h2>
                ⚙️ AI Provider Configuration
            </h2>

        </div>



        <div style="padding:25px;">


        <form method="POST"
        action="<?php echo e(route('admin.ai-settings.update')); ?>">

            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>



            <label>

                AI Provider


                <select class="input"
                name="provider"
                required>


                    <option value="adaptive"
                    <?php if(($setting?->provider ?? 'adaptive')=='adaptive'): echo 'selected'; endif; ?>>

                        Adaptive Local AI

                    </option>



                    <option value="openai"
                    <?php if($setting?->provider=='openai'): echo 'selected'; endif; ?>>

                        OpenAI

                    </option>



                    <option value="gemini"
                    <?php if($setting?->provider=='gemini'): echo 'selected'; endif; ?>>

                        Google Gemini

                    </option>



                    <option value="claude"
                    <?php if($setting?->provider=='claude'): echo 'selected'; endif; ?>>

                        Claude AI

                    </option>



                    <option value="azure"
                    <?php if($setting?->provider=='azure'): echo 'selected'; endif; ?>>

                        Azure OpenAI

                    </option>



                    <option value="openrouter"
                    <?php if($setting?->provider=='openrouter'): echo 'selected'; endif; ?>>

                        OpenRouter

                    </option>



                    <option value="ollama"
                    <?php if($setting?->provider=='ollama'): echo 'selected'; endif; ?>>

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
                value="<?php echo e($setting?->model ?? 'adaptive-local-v1'); ?>"
                placeholder="Example: gpt-5-mini">


            </label>



            <br>



            <label>

                API Key


                <input
                class="input"
                type="password"
                name="api_key"
                placeholder="<?php echo e($setting?->api_key ? 'Saved - enter new key' : 'Enter API key'); ?>">


            </label>



            <?php if($setting?->api_key): ?>

            <div class="info-note">

                ✅ API Key already configured.

                <br>

                Leave blank to keep existing key.

            </div>

            <?php endif; ?>



            <br>



            <label>

                Status


                <select class="input"
                name="status">


                    <option value="active"
                    <?php if(($setting?->status ?? 'active')=='active'): echo 'selected'; endif; ?>>

                        Active

                    </option>


                    <option value="inactive"
                    <?php if($setting?->status=='inactive'): echo 'selected'; endif; ?>>

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


<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/admin/ai-settings.blade.php ENDPATH**/ ?>
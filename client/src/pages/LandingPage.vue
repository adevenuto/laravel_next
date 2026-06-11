<script setup lang="ts">
import { ArrowRight, Compass, MessageCircle, Sparkles } from 'lucide-vue-next'
import AppFooter from '@/components/AppFooter.vue'
</script>

<template>
  <div>
    <div class="mx-auto w-full max-w-5xl px-5 py-10 sm:py-16 space-y-20 sm:space-y-28">
      <!-- Hero -->
      <section class="space-y-8 max-w-3xl">
        <div class="space-y-5">
          <p
            class="inline-flex items-center gap-2 rounded-pill bg-secondary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-secondary"
            style="color: hsl(var(--secondary))"
          >
            <Sparkles class="h-3.5 w-3.5" />
            A pattern-based Spanish course
          </p>
          <h1 class="font-display text-5xl sm:text-7xl font-semibold leading-[0.95] tracking-tight">
            Learn Spanish
            <span class="italic text-primary">in patterns,</span>
            not flashcards.
          </h1>
          <p class="text-lg sm:text-xl text-muted-foreground max-w-2xl leading-relaxed">
            One small rule unlocks hundreds of words. By the end of Unit 1.4 you'll have produced
            <span class="font-semibold text-foreground">400 Spanish words</span> that nobody taught
            you. By Level 1's last screen you'll hold a real conversation in a café.
          </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <RouterLink
            :to="{ name: 'signup' }"
            class="inline-flex h-14 items-center justify-center gap-2 rounded-soft bg-primary px-7 text-lg font-semibold text-primary-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
          >
            Start learning
            <ArrowRight class="h-5 w-5" />
          </RouterLink>
          <RouterLink
            :to="{ name: 'login' }"
            class="inline-flex h-14 items-center justify-center rounded-soft border-2 border-border bg-card/60 px-7 text-base font-semibold text-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
          >
            I have an account
          </RouterLink>
        </div>
      </section>

      <!-- WordForge teaser — the signature transformation -->
      <section class="sunlit-card p-8 sm:p-12 text-center space-y-6">
        <p class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">
          The pattern in action
        </p>
        <div class="flex flex-wrap items-baseline justify-center gap-3 sm:gap-6 leading-none">
          <span class="font-display text-4xl sm:text-6xl text-muted-foreground">information</span>
          <ArrowRight class="h-6 w-6 sm:h-8 sm:w-8 text-primary self-center" />
          <span class="es font-display text-5xl sm:text-7xl text-foreground">
            informa<span class="text-primary">ción</span>
          </span>
        </div>
        <p class="text-sm sm:text-base text-muted-foreground max-w-prose mx-auto">
          Any English word ending in <em>-tion</em> swaps to <em>-ción</em> in Spanish. Drop the
          accent on the <strong>ó</strong>. That's it. Apply the rule and you've already produced
          hundreds of words.
        </p>
      </section>

      <!-- Three feature cards -->
      <section class="space-y-8">
        <div class="space-y-3">
          <h2 class="font-display text-3xl sm:text-4xl font-semibold leading-tight">
            What makes it stick
          </h2>
          <p class="text-muted-foreground max-w-2xl">
            Three principles your high-school Spanish class missed.
          </p>
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
          <article class="sunlit-card p-6 space-y-3">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-pill bg-primary/15 text-primary"
            >
              <Sparkles class="h-5 w-5" />
            </div>
            <h3 class="font-display text-xl font-semibold leading-tight">Rules, not lists</h3>
            <p class="text-sm text-muted-foreground leading-relaxed">
              Memorize a pattern once, generate hundreds of words. The <em>-tion → -ción</em> rule
              alone unlocks 400+ everyday words.
            </p>
          </article>

          <article class="sunlit-card p-6 space-y-3">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-pill bg-secondary/15"
              style="color: hsl(var(--secondary))"
            >
              <MessageCircle class="h-5 w-5" />
            </div>
            <h3 class="font-display text-xl font-semibold leading-tight">
              Chunks over conjugation
            </h3>
            <p class="text-sm text-muted-foreground leading-relaxed">
              Phrases like <em class="es">¿cómo se dice…?</em> get you through any conversation.
              Grammar comes later — first, you sound human.
            </p>
          </article>

          <article class="sunlit-card p-6 space-y-3">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-pill text-success"
              style="background: hsl(var(--success) / 0.15); color: hsl(var(--success))"
            >
              <Compass class="h-5 w-5" />
            </div>
            <h3 class="font-display text-xl font-semibold leading-tight">Repair beats fluency</h3>
            <p class="text-sm text-muted-foreground leading-relaxed">
              Knowing how to ask
              <em class="es">más despacio, por favor</em>
              matters more than knowing every verb. We drill the rescue moves first.
            </p>
          </article>
        </div>
      </section>

      <!-- Final CTA -->
      <section
        class="sunlit-card p-8 sm:p-12 space-y-6 text-center"
        style="background: hsl(var(--primary) / 0.08)"
      >
        <h2 class="font-display text-3xl sm:text-5xl font-semibold leading-tight">
          Ready to actually speak Spanish?
        </h2>
        <p class="text-muted-foreground max-w-prose mx-auto">
          Level 1 is free to play. Start with the five vowels, finish at a café in Guadalajara
          ordering coffee from Sofía.
        </p>
        <RouterLink
          :to="{ name: 'signup' }"
          class="inline-flex h-14 items-center justify-center gap-2 rounded-soft bg-primary px-8 text-lg font-semibold text-primary-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
        >
          Get started
          <ArrowRight class="h-5 w-5" />
        </RouterLink>
      </section>
    </div>

    <AppFooter />
  </div>
</template>

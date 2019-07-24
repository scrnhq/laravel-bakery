<?php

namespace Bakery\Tests\Fixtures\Mutations;

use Bakery\Mutations\Mutation;
use Bakery\Support\Arguments;
use Bakery\Tests\Fixtures\Models\User;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Str;

class InviteUserMutation extends Mutation
{
    /**
     * Define the type of the RootField.
     *
     * @return Type
     */
    public function type(): Type
    {
        return \Bakery\Type::boolean();
    }

    /**
     * Define the arguments of the mutation.
     */
    public function args(): array
    {
        return [
            'input' => \Bakery\Type::of('InviteUserInput'),
        ];
    }

    /**
     * Define the validation rules of the mutation.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'input.email' => 'email',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'input.email' => 'email',
        ];
    }

    /**
     * Define the authorization check for the mutation.
     */
    public function authorize()
    {
        return auth()->user();
    }

    public function resolve(Arguments $arguments)
    {
        $user = new User();
        $user->name = Str::random();
        $user->email = $arguments->input->email;
        $user->password = Str::random();
        $user->save();

        return true;
    }
}

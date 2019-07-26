<?php

namespace Bakery\Tests\Fixtures\Mutations;

use Bakery\Type;
use Illuminate\Support\Str;
use Bakery\Support\Arguments;
use Bakery\Mutations\Mutation;
use Bakery\Tests\Fixtures\Models\User;
use Bakery\Types\Definitions\RootType;

class InviteUserMutation extends Mutation
{
    /**
     * Define the type of the RootField.
     *
     * @return RootType
     */
    public function type(): RootType
    {
        return Type::boolean();
    }

    /**
     * Define the arguments of the mutation.
     */
    public function args(): array
    {
        return [
            'input' => Type::of('InviteUserInput'),
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
        if (! auth()->user()) {
            $this->deny('You need to be logged in to do this!');
        }
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

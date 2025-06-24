# TYPO3 Documentation Rendering

Render documentation with the official TYPO3 Documentation rendering 
container.

## Usage

You will have to have a `Documentation` folder in your working directory. Then:

```yaml
      - name: Render documentation
        uses: TYPO3-Documentation/gh-render-action@<version>
        id: rendering
        # Optional configuration:
        # with:
          # if you are not rendering the current repository but a different one
          # enter the repository_url here:
          # repository_url: https://example.org/your-repository.git
          
          # if you want to render a branch different from the current branch enter the name: 
          # source_branch: 'main'
          
          # this is the directory name in the final folder structure used to contain this 
          # version of the repository. If not given, the source branch name is used.
          # target_branch_directory: 'latest'

      - name: Publish archive with result
        uses: actions/upload-artifact@v1
        with:
          name: Rendered
          path: ${{ steps.rendering.outputs.renderedPath }}
```

As you can see the actions provides the output path as output to use in further steps.
